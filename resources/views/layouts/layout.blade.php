<!-- resources/views/layouts/layout.blade.php -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Household')</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
          theme: {
            extend: {
              colors: {
                primary: '#1d4ed8',
                secondary: '#06b6d4',
                link: '#84cc16',
              }
            }
          }
        }
      </script>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
                const themeToggleBtn = document.getElementById('theme-toggle');
                const htmlClasses = document.documentElement.classList;
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme) {
                    htmlClasses.add(currentTheme);
                } else {
                    htmlClasses.add('dark'); // Default to dark mode
                }

                themeToggleBtn.addEventListener('click', function() {
                    if (htmlClasses.contains('dark')) {
                        htmlClasses.remove('dark');
                        htmlClasses.add('light');
                        localStorage.setItem('theme', 'light');
                    } else {
                        htmlClasses.remove('light');
                        htmlClasses.add('dark');
                        localStorage.setItem('theme', 'dark');
                    }
                });
            });
        </script>
</head>
<body class="font-sans antialiased dark:bg-black dark:text-white/50">
    <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
        <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header>
                    <div class="flex justify-between items-center py-4">
                        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold text-cyan-600 dark:text-blue-500">@yield('title')</h1>

                    </div>

                </header>

                <main class="mt-6">
                    @yield('content')
                </main>

                <footer class="py-16 text-center text-sm text-black dark:text-white/70">
                    <p>&copy; {{ date('Y') }} Household Contributions</p>
                </footer>

            </div>
        </div>
    </div>
    <!-- Add JavaScript files here -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
