<?php

namespace Laltu\Quasar\Commands;

use Github\AuthMethod;
use Github\Client;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


use function Laravel\Prompts\text;

class InstallQuaserProject extends Command implements PromptsForMissingInput
{
    protected $name = 'install:quaser-project';

    protected $description = 'Downloads and installs a project from Envato given a purchase code.';

    public function handle(): void
    {
        if (!$this->verifyGitHubToken()) {
            return;
        }

        if (!$this->verifyEnvatoLicense()) {
            return;
        }

        try {
            $repoName = $this->option('github-repo-name') ?? config('quasar.app_name');

            $this->createGitHubRepository($repoName, config('quasar.github.token'));

            $this->initializeRepository();
            $this->pushToGitHub();
            $this->createPullRequest();
            $this->updateRepository();

            $this->info('Quasar project installation complete!');

        } catch (\Exception $e) {
            $this->error("Installation failed: {$e->getMessage()}");
        }
    }

    private function verifyGitHubToken(): bool
    {
        $githubToken = config('quasar.github.token');

        if (!$githubToken) {
            $this->error('GitHub token is not set.');
            return false;
        }

        return true;
    }

    private function verifyEnvatoLicense(): bool
    {
        $response = Http::acceptJson()->post('https://support.scriptspheres.com/api/license-verify', [
            'envatoItemId' => $this->argument('envato-item-id'),
            'licenseKey' => $this->argument('envato-purchase-code'),
        ]);

        if (!$response->successful()) {
            $this->error("API call error: {$response->json('message')}"); // Assuming API error message
            return false;
        }

        // Additional validation of the API response if needed
        $this->info($response->json('message'));

        return true;
    }

    private function createGitHubRepository(string $name, string $githubToken): void
    {
        try {
            $client = new Client();
            $client->authenticate($githubToken, AuthMethod::ACCESS_TOKEN);

            $username = $this->option('github-user-name') ?? config('quasar.github.username');
            $repository = $client->repo()->show($username, $name);

            if (!$repository) {
                $repository = $client->repo()->create($name, '', '', false);
                $this->info("Repository created on GitHub: $name");
            }

            return;

        } catch (\Github\Exception\RuntimeException $e) {
            $this->error("Failed to create GitHub repository: {$e->getMessage()}");
            return;
        }
    }


    private function initializeRepository(): void
    {
        $directory = base_path();
        $branch = $this->option('github-branch') ?? 'master';
        $githubUsername = config('quasar.github.username');
        $githubToken = config('quasar.github.token');
        $repoName = $this->option('github-repo-name') ?? config('quasar.app_name');

        $remoteUrl = "https://$githubUsername:$githubToken@github.com/$githubUsername/$repoName.git";

        $commands = [
            "cd $directory",
            'git init',
            'git add .',
            'git commit -m "Initial commit"',
            "git branch -M $branch",
            "git remote add origin $remoteUrl"
        ];

        $this->runShellCommands($commands);
    }

    private function runShellCommands(array $commands): void
    {
        foreach ($commands as $command) {

            $process = Process::run($command);

            $this->info($process->output());
        }
    }

    private function pushToGitHub(): void
    {
        $branch = $this->option('github-branch') ?? 'master';
        $repoName = $this->option('github-repo-name') ?? config('quasar.app_name');

        try {
            Process::run("git push -u origin $branch");
            $this->info("Repository pushed to GitHub: $repoName");
        } catch (ProcessFailedException $exception) {
            $this->error("Failed to push the repository to GitHub: {$exception->getMessage()}");
        }
    }

    private function createPullRequest(): void
    {
        $response = Http::acceptJson()->post('https://support.scriptspheres.com/api/create-pr', [
            'envatoItemId' => $this->argument('envato-item-id'),
            'envatoPurchaseCode' => $this->argument('envato-purchase-code'),
            'gitRepoName' => $this->option('github-repo-name'),
            'gitBranch' => $this->option('github-branch')
        ]);

        if ($response->successful()) {
            $this->info('Pull request created successfully.');
        } else {
            $this->error("Failed to create pull request: {$response->body()}");
        }
    }

    private function updateRepository(): void
    {
        $directory = base_path();
        $remote = 'origin';
        $branch = $this->option('github-branch') ?? 'master';

        $commands = [
            "git fetch $remote",
            "git merge $remote/$branch"
        ];

        $this->runShellCommands($commands, $directory);
    }

    /**
     * Get the options for the command.
     *
     * @return array An array of options for the command.
     */
    public function getOptions(): array
    {
        return [
            ['github-user-name', null, InputOption::VALUE_OPTIONAL, 'GitHub user name'],
            ['github-repo-name', null, InputOption::VALUE_OPTIONAL, 'GitHub repository URL'],
            ['github-branch', null, InputOption::VALUE_OPTIONAL, 'Branch of the GitHub repository'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'Specify the database driver'],
            ['force', 'f', InputOption::VALUE_OPTIONAL, 'Force install if the directory exists'],
        ];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['envato-item-id', InputArgument::REQUIRED, 'Envato Item ID'],
            ['envato-purchase-code', InputArgument::REQUIRED, 'Envato Purchase Code'],
        ];
    }

    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output): void
    {
        if (!$input->getOption('github-user-name')) {
            $repoName = text(label: 'What is the GitHub username?', default: 'laltu-das');
            $input->setOption('github-user-name', $repoName);
        }

        if (!$input->getOption('github-repo-name')) {
            $repoName = text(label: 'What is the GitHub repository?', default: config('quasar.github.repository'));
            $input->setOption('github-repo-name', $repoName);
        }

        if (!$input->getOption('github-branch')) {
            $branchName = text(label: 'Enter the branch name?', default: 'master');
            $input->setOption('github-branch', $branchName);
        }
    }


    protected function replaceInFile(string $file, $search, $replace): void
    {
        File::put($file, Str::replace($search, $replace, File::get($file)));
    }

    protected function commitChanges(string $message): void
    {
        $directory = base_path();

        $commands = [
            'git add .',
            "git commit -m \"$message\""
        ];

        $this->runShellCommands($commands, $directory);
    }

    private function getDefaultBranch(): string
    {
        $process = Process::run('git config --global init.defaultBranch');

        return trim($process->output()) ?: 'main';
    }
}

