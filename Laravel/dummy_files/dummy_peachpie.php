<?php
namespace PeachPie;

class Runtime
{
    public static function isScriptCompiled(string $script): bool
    {
        return \Pchp\Core\Context::TryResolveScript(realpath(__DIR__ . "/../"), $script)->IsValid;
    }
}