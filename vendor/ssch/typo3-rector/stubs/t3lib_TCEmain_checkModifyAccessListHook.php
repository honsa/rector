<?php

namespace RectorPrefix20211010;

if (\class_exists('t3lib_TCEmain_checkModifyAccessListHook')) {
    return;
}
class t3lib_TCEmain_checkModifyAccessListHook
{
}
\class_alias('t3lib_TCEmain_checkModifyAccessListHook', 't3lib_TCEmain_checkModifyAccessListHook', \false);
