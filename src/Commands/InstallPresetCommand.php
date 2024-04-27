<?php

namespace Laltu\Quasar\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use function Laravel\Prompts\multiselect;

class InstallPresetCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-ui:preset {stack : The development stack that should be installed (react,vue)} {--typescript : Indicates if TypeScript is preferred for the Inertia stack (Experimental)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Breeze controllers and resources';

    /**
     * Delete the "node_modules" directory and remove the associated lock files.
     *
     * @return void
     */
    protected static function flushNodeModules(): void
    {
        // Remove node_modules directory and lock files
        (new Filesystem)->deleteDirectory(base_path('node_modules'));

        collect(['yarn.lock', 'package-lock.json'])->each(fn($file) => (new Filesystem)->delete(base_path($file)));
    }

    /**
     * Handle the console command.
     *
     * @return int|null
     */
    public function handle(): ?int
    {
        // Get the specified stack (react, vue) from command argument
        $stack = $this->argument('stack');

        // Validate stack argument
        if (!in_array($stack, ['vue', 'react'])) {
            $this->error('Invalid stack. Supported stacks are [vue] and [react].');
            return 1;
        }

        // Call appropriate installation method based on the specified stack
        return $stack === 'vue' ? $this->installInertiaVueStack() : $this->installInertiaReactStack();
    }

    /**
     * Install the Inertia Vue Breeze stack.
     *
     * @return int
     */
    protected function installInertiaVueStack(): int
    {
        // Upgrade NPM quasar-ui for Vue stack
        $this->updateNodePackages(fn($packages) => [
            "@vueuse/core" => "^10.6.1",
            "classnames" => "^2.3.2",
            "floating-vue" => "^2.0.0-beta.24",
            "lodash-es" => "^4.17.21",
            "tailwind-merge" => "^2.0.0",
            "nanoid" => "^5.0.4"
        ]);

        // Upgrade additional NPM quasar-ui for Vue stack
        $this->updateNodePackages(fn($packages) => [
            "laravel-precognition-vue-inertia" => "^0.5.2"
        ], false);

        // Ensure existence of required directories
        collect(['Components', 'Layouts', 'Pages'])->each(
            fn($directory) => (new Filesystem)->ensureDirectoryExists(resource_path("js/{$directory}"))
        );

        // Copy Vue stub files to the resource directory
        $this->copyDirectories('inertia-vue' . ($this->option('typescript') ? '-ts' : ''), 'vue');

        // Install and build Node dependencies
        $this->installAndBuildNodeDependencies();

        // Display success message
        $this->line('');
        $this->info('Breeze scaffolding installed successfully.');

        return 0;
    }

    /**
     * Upgrade the "package.json" file with the provided callback.
     *
     * @param callable $callback
     * @param bool $dev
     * @return void
     */
    protected static function updateNodePackages(callable $callback, bool $dev = true): void
    {
        // Check if "package.json" file exists
        if (!file_exists(base_path('package.json'))) {
            return;
        }

        // Determine whether to update devDependencies or dependencies
        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        // Read and decode the existing "package.json" file
        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        // Apply the callback to update the specified dependencies
        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        // Sort dependencies alphabetically
        ksort($packages[$configurationKey]);

        // Write the updated quasar-ui back to "package.json" file
        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    /**
     * Run the given shell commands.
     *
     * @param array $commands
     * @return void
     */
    protected function runCommands(array $commands): void
    {
        // Create a new Process to run the specified shell commands
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        // Enable TTY if applicable
        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> ' . $e->getMessage() . PHP_EOL);
            }
        }

        // Run the process and display the output
        $process->run(fn($type, $line) => $this->output->write('    ' . $line));
    }

    /**
     * Install the Inertia React Breeze stack.
     *
     * @return int
     */
    protected function installInertiaReactStack(): int
    {
        // Upgrade NPM quasar-ui for React stack
        $this->updateNodePackages(fn($packages) => [
            '@headlessui/react' => '^1.4.2',
            '@inertiajs/react' => '^1.0.0',
            '@tailwindcss/forms' => '^0.5.3',
            '@vitejs/plugin-react' => '^4.0.3',
            'autoprefixer' => '^10.4.12',
            'postcss' => '^8.4.18',
            'tailwindcss' => '^3.2.1',
            'react' => '^18.2.0',
            'react-dom' => '^18.2.0',
        ]);

        // Ensure existence of required directories
        collect(['Components', 'Layouts', 'Pages'])->each(fn($directory) => (new Filesystem)->ensureDirectoryExists(resource_path("js/{$directory}"))
        );

        // Copy React stub files to the resource directory
        $this->copyDirectories('inertia-react' . ($this->option('typescript') ? '-ts' : ''), 'react');

        // Install and build Node dependencies
        $this->installAndBuildNodeDependencies();

        // Display success message
        $this->line('');
        $this->info('Breeze scaffolding installed successfully.');

        return 0;
    }

    /**
     * Copy directories from the stub to the resource directory.
     *
     * @param string $stubPath
     * @param string $stack
     * @return void
     */
    protected function copyDirectories(string $stubPath, string $stack): void
    {
        $directories = ['Components', 'Layouts', 'Pages'];
        collect($directories)->each(fn($directory) => (new Filesystem)->copyDirectory(__DIR__ . "/../../stubs/{$stubPath}/resources/js/{$directory}", resource_path("js/{$directory}"))
        );
        if ($this->option('typescript')) {
            (new Filesystem)->copyDirectory(__DIR__ . "/../../stubs/{$stubPath}/resources/js/types", resource_path('js/types'));
        }
    }

    /**
     * Install and build Node dependencies based on the available lock files.
     *
     * @return void
     */
    protected function installAndBuildNodeDependencies(): void
    {
        $commands = [
            file_exists(base_path('pnpm-lock.yaml')) ? 'pnpm install' : (file_exists(base_path('yarn.lock')) ? 'yarn install' : 'npm install'),
            'npm run build',
        ];
        $this->runCommands($commands);
    }

    /**
     * Replace a given string within a given file.
     *
     * @param string $search
     * @param string $replace
     * @param string $path
     * @return void
     */
    protected function replaceInFile(string $search, string $replace, string $path): void
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        $stack = $input->getArgument('stack');

        // If the selected stack is React or Vue, prompt for optional features
        if (in_array($stack, ['react', 'vue'])) {
            collect(multiselect(
                label: 'Would you like any optional features?',
                options: ['typescript' => 'TypeScript (experimental)'],
            ))->each(fn($option) => $input->setOption($option, true));
        }
    }
}
