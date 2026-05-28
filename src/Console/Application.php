<?php

namespace Nikogin\Framework\Console;

class Application
{
    /** @var array<string, class-string<Command>> */
    private array $commands = [];

    /**
     * @param array<string, class-string<Command>> $commands Keyed by command name.
     */
    public function __construct(private string $basePath, array $commands = [])
    {
        foreach ($commands as $name => $class) {
            $this->add($name, $class);
        }
    }

    /** @param class-string<Command> $class */
    public function add(string $name, string $class): void
    {
        $this->commands[$name] = $class;
    }

    public function run(array $argv): void
    {
        $commandName = $argv[1] ?? null;

        if (!$commandName) {
            $this->error('No command provided. Available: ' . implode(', ', array_keys($this->commands)));
            exit(1);
        }

        if (!isset($this->commands[$commandName])) {
            $this->error("Unknown command: {$commandName}");
            exit(1);
        }

        [$args, $options] = $this->parseArgv(array_slice($argv, 2));

        (new $this->commands[$commandName]())->handle($args, $options, $this->basePath);
    }

    private function parseArgv(array $argv): array
    {
        $args    = [];
        $options = [];

        foreach ($argv as $token) {
            if (str_starts_with($token, '--')) {
                $token = ltrim($token, '--');
                if (str_contains($token, '=')) {
                    [$key, $value] = explode('=', $token, 2);
                } else {
                    $key   = $token;
                    $value = true;
                }
                $options[$key] = $value;
            } else {
                $args[] = $token;
            }
        }

        return [$args, $options];
    }

    private function error(string $message): void
    {
        fwrite(STDERR, "\033[31mError:\033[0m {$message}" . PHP_EOL);
    }
}
