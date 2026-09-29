<?php

namespace App\Core\Contracts;

interface AddonInterface
{
    /**
     * Unique addon slug / identifier (e.g. 'vendor-marketplace', 'promotion-engine').
     */
    public function id(): string;

    /**
     * Human-readable name.
     */
    public function name(): string;

    /**
     * Addon semantic version.
     */
    public function version(): string;

    /**
     * Description of functionality.
     */
    public function description(): string;

    /**
     * Whether this addon is currently enabled.
     */
    public function isEnabled(): bool;

    /**
     * Boot logic when addon is activated in the platform.
     */
    public function boot(AddonContext $context): void;
}
