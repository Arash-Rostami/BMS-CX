<?php

use App\Services\AvatarMaker;


function getTableDesign()
{
    return data_get(optional(auth()->user()->info), 'tableDesign');
}

function isUserAdmin()
{
    return auth()->user()->role === 'admin';
}


function isUserManager()
{
    return auth()->user()->role === 'manager';
}


function isUserAgent()
{
    return auth()->user()->role === 'agent';
}


function isUserAccountant()
{
    return auth()->user()->role === 'accountant';
}
