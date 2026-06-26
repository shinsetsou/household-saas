@extends('organizations.settings.layout', ['activeTab' => 'members'])

@section('tab-content')

<div class="space-y-6">
    <div class="rounded-xl shadow-sm bg-white px-6 py-6">
        <h2 class="text-lg font-semibold text-gray-900">Invite a member</h2>
        <p class="mt-1 text-sm text-gray-500">Admins can manage billing and members. Treasurers can verify contributions and record expenses. Viewers have read-only access.</p>

        <form method="POST" action="{{ route('organizations.settings.members.invite', $organization->slug) }}" class="mt-6 flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="flex-1">
                <input type="email" name="email" required maxlength="255"
                       placeholder="treasurer@example.com"
                       value="{{ old('email') }}"
                       class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <select name="role" class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" {{ old('role', 'viewer') === $role->value ? 'selected' : '' }}>
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>
                @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">
                Send invite
            </button>
        </form>
    </div>

    @if($pendingInvitations->isNotEmpty())
        <div class="rounded-xl shadow-sm bg-white px-6 py-6">
            <h2 class="text-lg font-semibold text-gray-900">Pending invitations</h2>
            <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="py-2">Email</th>
                        <th class="py-2">Role</th>
                        <th class="py-2">Expires</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pendingInvitations as $invitation)
                        <tr>
                            <td class="py-2 text-gray-700">{{ $invitation->email }}</td>
                            <td class="py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                    {{ $invitation->role()->label() }}
                                </span>
                            </td>
                            <td class="py-2 text-gray-500">{{ $invitation->expires_at->format('M j, Y') }}</td>
                            <td class="py-2 text-right space-x-2">
                                <form method="POST" action="{{ route('organizations.settings.invitations.resend', [$organization->slug, $invitation->id]) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">Resend</button>
                                </form>
                                <form method="POST" action="{{ route('organizations.settings.invitations.revoke', [$organization->slug, $invitation->id]) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="rounded-xl shadow-sm bg-white px-6 py-6">
        <h2 class="text-lg font-semibold text-gray-900">Members ({{ $members->count() }})</h2>
        <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                    <th class="py-2">Name</th>
                    <th class="py-2">Email</th>
                    <th class="py-2">Role</th>
                    <th class="py-2">Joined</th>
                    <th class="py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($members as $member)
                    <tr>
                        <td class="py-2 font-medium text-gray-900">{{ $member['user']->name }}</td>
                        <td class="py-2 text-gray-700">{{ $member['user']->email }}</td>
                        <td class="py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                @switch($member['role']->value)
                                    @case('admin') bg-indigo-100 text-indigo-700 @break
                                    @case('treasurer') bg-green-100 text-green-700 @break
                                    @default bg-gray-100 text-gray-700
                                @endswitch
                            ">{{ $member['role']->label() }}</span>
                        </td>
                        <td class="py-2 text-gray-500">{{ $member['joined_at']->format('M j, Y') }}</td>
                        <td class="py-2 text-right space-x-2">
                            <form method="POST" action="{{ route('organizations.settings.members.role', [$organization->slug, $member['user']->id]) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <select name="role" onchange="this.form.submit()" class="text-xs rounded border-gray-300">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->value }}" {{ $member['role'] === $role ? 'selected' : '' }}>{{ $role->label() }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <form method="POST" action="{{ route('organizations.settings.members.remove', [$organization->slug, $member['user']->id]) }}" class="inline" onsubmit="return confirm('Remove this member?');">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-red-700 hover:text-red-900">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection