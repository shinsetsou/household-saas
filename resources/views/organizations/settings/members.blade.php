@extends('organizations.settings.layout', ['activeTab' => 'members'])

@section('tab-content')

@php
    $roleMeta = [
        'admin'     => ['label' => 'Admin',     'tone' => 'indigo', 'icon' => 'shield'],
        'treasurer' => ['label' => 'Treasurer', 'tone' => 'green',  'icon' => 'wallet'],
        'viewer'    => ['label' => 'Viewer',    'tone' => 'slate',  'icon' => 'eye'],
    ];
@endphp

<div class="space-y-6" x-data="{ showCopied: null }">

    {{-- Flash banner --}}
    @if (session('info') && str_starts_with((string) session('info'), 'http'))
        <div class="flex items-start gap-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 ring-1 ring-indigo-200 dark:ring-indigo-800 p-4">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 0l1.415 1.415a4 4 0 010 5.656l-3 3a4 4 0 01-5.656 0M10.172 13.828a4 4 0 01-5.656 0l-1.415-1.415a4 4 0 010-5.656l3-3a4 4 0 015.656 0"/></svg>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-100">Invitation link</p>
                <p class="mt-1 text-xs text-indigo-700 dark:text-indigo-200 break-all font-mono">{{ session('info') }}</p>
            </div>
        </div>
    @endif

    {{-- Invite a member card --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <div class="flex-1">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Invite a member</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">They'll get an email with a secure link that expires in 14 days.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('organizations.settings.members.invite', $organization->slug) }}"
              x-data="{ role: '{{ old('role', 'viewer') }}' }"
              class="px-6 py-6 space-y-5">
            @csrf

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Email address</label>
                    <input id="email" type="email" name="email" required maxlength="255"
                           placeholder="treasurer@yourhoa.ph"
                           value="{{ old('email') }}"
                           class="mt-2 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
                    @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Role</label>
                    <input type="hidden" name="role" :value="role">
                    <div class="mt-2 grid grid-cols-3 gap-2" role="radiogroup" aria-label="Role">
                        @foreach($roles as $role)
                            @php $meta = $roleMeta[$role->value]; @endphp
                            <button type="button"
                                    role="radio"
                                    :aria-checked="role === '{{ $role->value }}'"
                                    @click="role = '{{ $role->value }}'"
                                    :class="role === '{{ $role->value }}'
                                        ? 'ring-2 ring-brand-500 bg-brand-50 dark:bg-brand-900/30 border-brand-300'
                                        : 'ring-1 ring-slate-200 dark:ring-slate-700 hover:ring-slate-300'"
                                    class="flex flex-col items-start gap-1 rounded-xl border border-transparent px-3 py-2.5 text-left transition">
                                <span class="text-xs font-semibold {{ $role->value === 'admin' ? 'text-indigo-700' : ($role->value === 'treasurer' ? 'text-green-700' : 'text-slate-600') }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">
                                    {{ $role->description() }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                    @error('role')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center justify-between pt-1">
                <p class="text-xs text-slate-400">Need a quick start? Try the Treasurer role — they can verify contributions on day one.</p>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand-600 to-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-brand-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Send invitation
                </button>
            </div>
        </form>
    </div>

    {{-- Pending invitations --}}
    @if($pendingInvitations->isNotEmpty())
        <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-100 text-amber-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">Pending invitations</h2>
                        <p class="text-xs text-slate-500">{{ $pendingInvitations->count() }} awaiting acceptance</p>
                    </div>
                </div>
            </div>

            <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach($pendingInvitations as $invitation)
                    @php
                        $expiresInDays = (int) now()->diffInDays($invitation->expires_at, false);
                        $expiringSoon = $expiresInDays <= 3 && ! $invitation->isExpired();
                    @endphp
                    <li class="px-6 py-4 flex flex-wrap items-center gap-4">
                        <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-amber-100 to-pink-100 text-amber-700 text-sm font-bold uppercase">
                            {{ strtoupper(substr($invitation->email, 0, 1)) }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $invitation->email }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                @php $meta = $roleMeta[$invitation->role]; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                    {{ $invitation->role === 'admin' ? 'bg-indigo-100 text-indigo-700' :
                                       ($invitation->role === 'treasurer' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span>·</span>
                                <span>
                                    @if($invitation->isExpired())
                                        <span class="text-red-600 font-semibold">Expired</span>
                                    @elseif($expiringSoon)
                                        Expires in {{ $expiresInDays }} {{ \Illuminate\Support\Str::plural('day', $expiresInDays) }}
                                    @else
                                        Expires {{ $invitation->expires_at->format('M j, Y') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <button type="button"
                                    data-copy-url="{{ URL::temporarySignedRoute('invitations.show', $invitation->expires_at, ['invitation' => $invitation->token]) }}"
                                    onclick="copyInviteLink(this)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                                    title="Copy invite link">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span>Copy link</span>
                            </button>

                            <form method="POST" action="{{ route('organizations.settings.invitations.resend', [$organization->slug, $invitation->token]) }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-900/30 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Resend
                                </button>
                            </form>

                            <form method="POST" action="{{ route('organizations.settings.invitations.revoke', [$organization->slug, $invitation->token]) }}" class="inline" onsubmit="return confirm('Revoke this invitation? The link will stop working immediately. You can resend a fresh link from this page if needed.');">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                    Revoke
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Members --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 ring-1 ring-slate-200 dark:ring-slate-700 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-brand-500 to-violet-600 text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Members</h2>
                    <p class="text-xs text-slate-500">{{ $members->count() }} total</p>
                </div>
            </div>
        </div>

        <ul class="divide-y divide-slate-100 dark:divide-slate-700">
            @forelse($members as $member)
                @php
                    $meta = $roleMeta[$member['role']->value];
                    $initials = strtoupper(substr($member['user']->name ?? '?', 0, 1));
                    $gradient = $member['role']->value === 'admin' ? 'from-indigo-500 to-violet-600'
                        : ($member['role']->value === 'treasurer' ? 'from-emerald-500 to-green-600' : 'from-slate-400 to-slate-600');
                @endphp
                <li class="px-6 py-4 flex flex-wrap items-center gap-4">
                    <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br {{ $gradient }} text-white text-sm font-bold">
                        {{ $initials }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $member['user']->name }}</p>
                            @php $meta = $roleMeta[$member['role']->value]; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
                                {{ $member['role']->value === 'admin' ? 'bg-indigo-100 text-indigo-700' :
                                   ($member['role']->value === 'treasurer' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700') }}">
                                {{ $meta['label'] }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 truncate">
                            {{ $member['user']->email }} · joined {{ $member['joined_at']->format('M j, Y') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('organizations.settings.members.role', [$organization->slug, $member['user']->id]) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <select name="role" onchange="if(confirm('Change this member\\'s role?')) this.form.submit();"
                                    class="text-xs rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-2.5 py-1.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                @foreach($roles as $role)
                                    <option value="{{ $role->value }}" {{ $member['role'] === $role ? 'selected' : '' }}>{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </form>

                        <form method="POST" action="{{ route('organizations.settings.members.remove', [$organization->slug, $member['user']->id]) }}" class="inline" onsubmit="return confirm('Remove this member? They will lose access immediately.');">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                Remove
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="px-6 py-12 text-center text-sm text-slate-500">No members yet.</li>
            @endforelse
        </ul>
    </div>
</div>

<script>
    async function copyInviteLink(button) {
        const url = button.dataset.copyUrl;
        try {
            await navigator.clipboard.writeText(url);
            const original = button.querySelector('span')?.textContent ?? 'Copy link';
            button.querySelector('span').textContent = 'Copied!';
            button.classList.add('text-emerald-600');
            setTimeout(() => {
                button.querySelector('span').textContent = original;
                button.classList.remove('text-emerald-600');
            }, 1500);
        } catch (e) {
            // Fallback: select+copy via temporary input
            const ta = document.createElement('textarea');
            ta.value = url;
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (_) {}
            document.body.removeChild(ta);
            button.querySelector('span').textContent = 'Copied!';
            setTimeout(() => { button.querySelector('span').textContent = 'Copy link'; }, 1500);
        }
    }
</script>

@endsection