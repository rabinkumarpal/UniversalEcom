<?php

namespace App\Core\Contracts;

/**
 * Standard contract for portable add-ons adhering to 22_PORTABLE_ADDON_ARCHITECTURE.md.
 */
interface EcommerceAddon extends AddonInterface
{
    // Inherits id(), name(), version(), description(), isEnabled(), and boot(AddonContext $context): void
}
