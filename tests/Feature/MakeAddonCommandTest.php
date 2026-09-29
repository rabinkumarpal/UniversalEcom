<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeAddonCommandTest extends TestCase
{
    protected string $testPackageDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPackageDir = base_path('packages/sample-scaffold-addon');

        if (File::exists($this->testPackageDir)) {
            File::deleteDirectory($this->testPackageDir);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testPackageDir)) {
            File::deleteDirectory($this->testPackageDir);
        }

        parent::tearDown();
    }

    public function test_make_addon_command_scaffolds_complete_portable_package(): void
    {
        $this->artisan('make:addon', [
            'name' => 'Sample Scaffold Addon',
            '--description' => 'Test portable add-on package specification verification',
        ])
            ->expectsOutputToContain('Scaffolding portable add-on [SampleScaffoldAddon]')
            ->expectsOutputToContain('Successfully generated add-on')
            ->assertSuccessful();

        $this->assertDirectoryExists($this->testPackageDir);
        $this->assertFileExists($this->testPackageDir.'/composer.json');
        $this->assertFileExists($this->testPackageDir.'/src/SampleScaffoldAddonAddon.php');
        $this->assertFileExists($this->testPackageDir.'/src/SampleScaffoldAddonServiceProvider.php');
        $this->assertFileExists($this->testPackageDir.'/config/sample-scaffold-addon.php');
        $this->assertFileExists($this->testPackageDir.'/routes/web.php');
        $this->assertFileExists($this->testPackageDir.'/routes/api.php');
        $this->assertFileExists($this->testPackageDir.'/resources/views/index.blade.php');
        $this->assertFileExists($this->testPackageDir.'/README.md');

        // Verify composer.json contents
        $composerJson = json_decode(File::get($this->testPackageDir.'/composer.json'), true);
        $this->assertEquals('universal-ecom/sample-scaffold-addon', $composerJson['name']);
        $this->assertEquals('Packages\\SampleScaffoldAddon\\', array_key_first($composerJson['autoload']['psr-4']));

        // Verify that the generated Addon class code contains proper interfaces
        $addonCode = File::get($this->testPackageDir.'/src/SampleScaffoldAddonAddon.php');
        $this->assertStringContainsString('implements EcommerceAddon', $addonCode);
        $this->assertStringContainsString('public function boot(AddonContext $context): void', $addonCode);

        // Verify that running command again fails due to existing directory
        $this->artisan('make:addon', [
            'name' => 'Sample Scaffold Addon',
        ])
            ->expectsOutputToContain('already exists')
            ->assertFailed();
    }
}
