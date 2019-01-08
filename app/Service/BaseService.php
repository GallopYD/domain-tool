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
     * api validate 不通过没有具体信息修正
     * @see(https://github.com/dingo/api/issues/584)
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

    /**
     * Throw the failed validation exception.
     * api validate 不通过没有具体信息修正
     * @see(https://github.com/dingo/api/issues/584)
     * @param Request $request
     * @param $validator
     */
    protected function throwValidationException(\Illuminate\Http\Request $request, $errors) {
        throw new ValidationHttpException($errors);
    }
}