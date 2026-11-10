<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->laravel();

arch('application code reads configuration through config() instead of env()')
    ->expect('env')
    ->not->toBeUsed();

arch('models extend the Eloquent base model')
    ->expect('App\Models')
    ->toExtend(Model::class);

arch('controllers do not build raw database queries')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('form requests extend FormRequest and end with Request')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class)
    ->toHaveSuffix('Request');

arch('actions do not depend on the HTTP layer')
    ->expect('App\Actions')
    ->not->toUse(['request', 'session', 'redirect', 'response']);

arch('queued jobs implement ShouldQueue')
    ->expect('App\Jobs')
    ->toImplement(ShouldQueue::class);

arch('domain value sets are enums')
    ->expect('App\Enums')
    ->toBeEnums();
