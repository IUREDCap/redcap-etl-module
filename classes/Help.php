<?php

namespace IU\RedCapEtlModule;

class Help
{
    /** @var array map from help topic to help content */
    private static $info =
        ['test' => 'This is a test'.'.']
        ;

    public static function getHelp($topic)
    {
        return self::$info[$topic];
    }
}


print Help::getHelp('test')."\n";

