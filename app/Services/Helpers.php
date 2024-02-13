<?php

use App\Services\AvatarMaker;


function getTableDesign()
{
    return data_get(optional(auth()->user()->info), 'tableDesign');
}

