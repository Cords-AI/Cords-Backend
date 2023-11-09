<?php

namespace App\Admin\Inquiry;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class InquiryController extends AbstractController
{
    #[Get('/admin/inquiry')]
    public function get(ManagerRegistry $doctrine, Request $request): JsonResponse
    {
        $inquiries = new InquiryCollection($doctrine);
        $inquiries
            ->offset($request->get('offset'))
            ->limit($request->get('limit'))
            ->order($request->get('order'))
            ->dir($request->get('dir'))
            ->build()
        ;
        return new JsonResponse($inquiries);
    }
}
