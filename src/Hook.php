<?php

namespace BrainMaestro\GitHooks;

use Exception;

class Hook
{
    const LOCK_FILE = 'cghooks.lock';

    /**
     * Get config section of the composer config file.
     *
     * @param  string $dir dir where to look for composer.json
     * @param  string $section config section to fetch in the composer.json
     *
     * @return array
     */
    public static function getConfig($dir, $section)
    {
        if (in_array($section, ['command'])) {
            throw new Exception("Invalid config section [{$section}]. Available sections: command.");
        }

        $composerFile = "{$dir}/composer.json";
        if (!file_exists($composerFile)) {
            return [];
        }

        $contents = file_get_contents($composerFile);
        $json = json_decode($contents, true);
        if (! isset($json['extra']['hooks']['config'][$section])) {
            return [];
        }

        return $json['extra']['hooks']['config'][$section];
    }

    /**
     * Check if the given hook is a sequence of commands.
     *
     * @param string $dir
     * @param string $hook
     * @return bool
     */
    public static function isHookWithCommandsSequence($dir, $hook)
    {
        $hooksWithSequence = self::getConfig($dir, 'commands');

        return in_array($hook, $hooksWithSequence);
    }

    /**
     * Get scripts section of the composer config file.
     *
     * @param	$dir	string	dir where to look for composer.json
     *
     * @return array
     */
    public static function getValidHooks($dir)
    {
        $composerFile = "{$dir}/composer.json";
        if (!file_exists($composerFile)) {
            return [];
        }

        $contents = file_get_contents($composerFile);
        $json = json_decode($contents, true);
        $hooks = array_merge(
            isset($json['scripts']) ? $json['scripts'] : [],
            isset($json['hooks']) ? $json['hooks'] : [],
            isset($json['extra']['hooks']) ? $json['extra']['hooks'] : []
        );
        $validHooks = [];

        foreach ($hooks as $hook => $script) {
            if (self::isValidHook($hook)) {
                $validHooks[$hook] = $script;
            }
        }

        return $validHooks;
    }

    /**
     * Check if a hook is valid
     */
    public static function isValidHook($hook)
    {
        return array_key_exists($hook, self::getHooks());
    }

    /**
     * Get all valid git hooks
     */
    private static function getHooks()
    {
        return array_flip([
           'applypatch-msg',
           'commit-msg',
           'post-applypatch',
           'post-checkout',
           'post-commit',
           'post-merge',
           'post-receive',
           'post-rewrite',
           'post-update',
           'pre-applypatch',
           'pre-auto-gc',
           'pre-commit',
           'pre-push',
           'pre-rebase',
           'pre-receive',
           'prepare-commit-msg',
           'push-to-checkout',
           'update',
        ]);
    }

    /**
     * Return hook contents
     *
     * @param string $dir
     * @param array|string $contents
     * @param string $hook
     *
     * @return string
     */
    public static function getHookContents($dir, $contents, $hook)
    {
        if (is_array($contents)) {
            $commandsSequence = Hook::isHookWithCommandsSequence($dir, $hook);
            $separator = $commandsSequence ? ' && \\'.PHP_EOL : PHP_EOL;
            $contents = implode($separator, $contents);
        }

        return $contents;
    }
}
