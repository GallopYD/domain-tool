<?php

namespace App\Service;


use App\Exceptions\ValidationHttpException;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

class BaseService
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $cache_enable;

    public function __construct()
    {
        $this->cache_enable = config('tool.cache_enable');
    }

    /**
     * Get a validation factory instance.
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidationFactory()
    {
        return app(Factory::class);
    }

    /**
     * validate
     *
     * @param Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $msg = $validator->errors()->first();
            throw new ValidationHttpException($msg);
        }
    }
}
