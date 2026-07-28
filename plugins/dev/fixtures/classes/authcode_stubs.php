<?php
/**
 * DEV-ONLY stubs for the gitignored production AuthCode classes
 * (secret holders for Discord / HeroesProfile / Mailchimp APIs).
 * Loaded by Dev\Fixtures\Plugin when the real classes are absent.
 * Every method returns an empty string; callers treat the resulting
 * failed API calls as no-ops.
 */

namespace Rikki\Heroeslounge\classes\Discord {
    class AuthCode
    {
        public static function __callStatic($name, $arguments)
        {
            return '';
        }
    }
}

namespace Rikki\Heroeslounge\classes\MMR {
    class AuthCode
    {
        public static function __callStatic($name, $arguments)
        {
            return '';
        }
    }
}

namespace Rikki\Heroeslounge\classes\Mailchimp {
    class AuthCode
    {
        public static function __callStatic($name, $arguments)
        {
            return '';
        }
    }
}
