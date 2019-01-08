<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationHttpException;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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
            throw new ValidationHttpException($validator->errors()->first());
        }
    }


    /**
     * Throw the failed validation exception.
     * api validate 不通过没有具体信息修正
     * @see(https://github.com/dingo/api/issues/584)
     * @param Request $request
     * @param $validator
     */
    protected function throwValidationException(\Illuminate\Http\Request $request, $validator) {
        throw new ValidationHttpException($validator->errors());
    }
}
