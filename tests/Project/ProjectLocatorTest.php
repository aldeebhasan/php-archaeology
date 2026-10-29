<?php

declare(strict_types=1);

namespace CodeArchaeology\Tests\Project;

use CodeArchaeology\Project\ProjectLocator;
use CodeArchaeology\Project\ProjectNotFound;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ProjectLocatorTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = (string) realpath(sys_get_temp_dir()) . '/archaeo-' . bin2hex(random_bytes(4));
        mkdir($this->tmp);
    }

    protected function tearDown(): void
    {
        (new Process(['rm', '-rf', $this->tmp]))->run();
    }

    public function testFindsRootFromNestedFolder(): void
    {
        $this->makeRepo($this->tmp);
        mkdir($this->tmp . '/src/Deep', 0777, true);

        $project = (new ProjectLocator())->locate($this->tmp . '/src/Deep');

        self::assertSame($this->tmp, $project->root);
        self::assertSame($this->tmp . '/.archaeology/dig.sqlite', $project->dbPath);
        self::assertSame($this->tmp . '/archaeology.php', $project->configPath);
        self::assertFalse($project->isShallow());
        self::assertFalse($project->isDirty());
    }

    public function testFailsWithoutComposerJson(): void
    {
        $this->expectException(ProjectNotFound::class);
        $this->expectExceptionMessage('No composer.json found');

        (new ProjectLocator())->locate($this->tmp);
    }

    public function testFailsWithoutGit(): void
    {
        touch($this->tmp . '/composer.json');

        $this->expectException(ProjectNotFound::class);
        $this->expectExceptionMessage('No git history found. Code Archaeology needs a git repository to dig.');

        (new ProjectLocator())->locate($this->tmp);
    }

    public function testDetectsShallowCloneAndDirtyTree(): void
    {
        $this->makeRepo($this->tmp . '/origin');
        $this->git($this->tmp, 'clone', '--quiet', '--depth=1', 'file://' . $this->tmp . '/origin', 'clone');
        file_put_contents($this->tmp . '/clone/new.php', '<?php');

        $project = (new ProjectLocator())->locate($this->tmp . '/clone');

        self::assertTrue($project->isShallow());
        self::assertTrue($project->isDirty());
    }

    /** shortcut: inline git setup, replace with tests/Support/GitRepoBuilder once 2.1 exists */
    private function makeRepo(string $dir): void
    {
        @mkdir($dir);
        file_put_contents($dir . '/composer.json', '{}');
        $this->git($dir, 'init', '--quiet');
        $this->git($dir, 'add', '.');
        $this->git($dir, 'commit', '--quiet', '-m', 'one');
        file_put_contents($dir . '/a.php', '<?php');
        $this->git($dir, 'add', '.');
        $this->git($dir, 'commit', '--quiet', '-m', 'two');
    }

    private function git(string $cwd, string ...$args): void
    {
        (new Process(['git', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', '-c', 'commit.gpgsign=false', ...$args], $cwd))->mustRun();
    }
}
