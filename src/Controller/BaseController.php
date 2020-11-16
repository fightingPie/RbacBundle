<?php

namespace SymfonyRbac\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Class BaseController
 * @package SymfonyRbac\Controller
 */
class BaseController extends AbstractController
{

    /**
     * @param $data
     * @param int $page
     * @param int $pageSize
     * @param int $total
     * @return JsonResponse
     */
    protected function paginatedJson($data, $page = 1, $pageSize = 10, $total = 1)
    {
        return $this->json([
            'items' => $data,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
            ]
        ], 200, [], [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['__initializer__', '__cloner__', '__isInitialized__']
        ]);
    }

    /**
     * @param Request $request
     * @param false $array
     * @return mixed
     */
    protected function parse(Request $request, $array = false)
    {

        $deserialized = json_decode(
            $request->getContent(),
            $array
        );

        $error = strval(json_last_error());

        /*
         * handle error,
         * simple map error to customize http error,
         * make it debug friendly.
         */
        if ($error != 0) {

            throw new BadRequestHttpException();
        }

        return $deserialized;
    }
}
