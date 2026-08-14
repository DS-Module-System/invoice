<?php

namespace App\Controller\Invoice;

use App\Entity\Core\Setting;
use App\Form\Invoice\SettingCompanyInfoForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class InvoiceSettingController extends AbstractController
{


    #[Route('/admin/invoice/setting/company-info', name: 'invoice_setting_company_info')]
    #[IsGranted('ROLE_INVOICE_VIEW')]
    public function index(EntityManagerInterface $em, Request $request, SluggerInterface $slugger): Response
    {

        $property = 'company_info';

        $row = $em->getRepository(Setting::class)->findOneBy(['property' => $property]);

        if(!$row) {
            $row = new Setting();
            $row->setProperty($property);
            $row->setValue([
                'city' => '',
                'country' => '',
                'address' => '',
                'eek' => ''
            ]);
            $em->persist($row);
            $em->flush();
        }

        $value = $row->getValue();

        if($request->isMethod('POST')) {
            $removeBtn = $request->request->get('removeLogo', null);
            if($removeBtn == 1) {
                $value['companyLogo'] = '';
                $row->setValue($value);
                $em->persist($row);
                $em->flush();
                $this->addFlash('success', 'Успешно премахнахте логото!');
                return $this->redirectToRoute('admin_setting_company_info');
            }
        }

        $form = $this->createForm(SettingCompanyInfoForm::class, $value);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $value = $form->getData();
            $companyLogo = $form->get('companyLogo')->getData();
            if ($companyLogo) {
                $originalFilename = pathinfo((string) $companyLogo->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$companyLogo->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $companyLogo->move(
                        $this->getParameter('kernel.project_dir').'/public/invoice/setting_files',
                        $newFilename
                    );
                } catch (FileException) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $value['companyLogo'] = $newFilename;
            }

            $row->setValue($value);
            $em->persist($row);
            $em->flush();
            $this->addFlash('success', 'Успешно запазихте данните!');
            return $this->redirectToRoute('invoice_setting_company_info');
        }


        return $this->render('invoice/company_info.html.twig', [
            'form' => $form->createView(),
            'companyLogo' => $value['companyLogo'] ?? '',
        ]);
    }

}
