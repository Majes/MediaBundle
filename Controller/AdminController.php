<?php

namespace Majes\MediaBundle\Controller;

use Majes\CoreBundle\Controller\SystemController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Response;

use Majes\MediaBundle\Entity\Media;

use Majes\MediaBundle\Form\MediaType;


class AdminController extends Controller implements SystemController
{
    /**
     * @Secure(roles="ROLE_MEDIA_LIST,ROLE_SUPERADMIN")
     *
     */
    public function listAction($context)
    {
        $_results_per_page = 20;

        $request = $this->getRequest();

        $types = $request->get('types');
        $folders = $request->get('folders');
        $page = $request->get('page');
        $loadmore = false;

        $em = $this->getDoctrine()->getManager();

        if(!is_null($types) && in_array('', $types)) $types = null;
        if(!is_null($folders) && in_array('', $folders)) $folders = null;
        if(is_null($page)) $page = 1;

        if(!is_null($types) || !is_null($folders)){
            $medias = $em->getRepository('MajesMediaBundle:Media')
                ->findForAdmin($types, $folders, $page, $_results_per_page);
        }else{
            $medias = $em->getRepository('MajesMediaBundle:Media')
                ->findForAdmin(null, null, $page, $_results_per_page);
        }

        $loadmore = count($medias) > $_results_per_page ? true : false;
        count($medias) > $_results_per_page ? array_pop($medias) : $medias;

        //Get all folders
        $all_folders =  $em->getRepository('MajesMediaBundle:Media')->listFolders();

        if($request->isXmlHttpRequest()){
            return $this->render('MajesMediaBundle:Admin:ajax/list-results.html.twig', array(
                'medias' => $medias,
                'loadmore' => $loadmore,
                'page' => $page,
                'context' => $context
                ));
        }else
            return $this->render('MajesMediaBundle:Admin:list.html.twig', array(
                'pageTitle' => $this->_translator->trans('Media management'),
                'pageSubTitle' => $this->_translator->trans('List of all media created'),
                'medias' => $medias,
                'folders' => $folders,
                'all_folders' => $all_folders,
                'types' => $types,
                'loadmore' => $loadmore,
                'page' => $page,
                'context' => $context
                ));
    }

    /**
     * @Secure(roles="ROLE_MEDIA_EDIT,ROLE_SUPERADMIN")
     *
     */
    public function editAction($id, $context)
    {
        $request = $this->getRequest();

        $em = $this->getDoctrine()->getManager();
        $media = $em->getRepository('MajesMediaBundle:Media')
            ->findOneById($id);


        $form = $this->createForm(new MediaType(), $media);

        if($request->getMethod() == 'POST'){

            $form->handleRequest($request);
            if ($form->isValid()) {

                if(is_null($media)){
                    $media = $form->getData();
                    $media->setCreateDate(new \DateTime(date('Y-m-d H:i:s')));
                    $media->setUser($this->_user);
                }

                $em = $this->getDoctrine()->getManager();

                $em->persist($media);
                $em->flush();

                if($context == 'full')
                    return $this->redirect($this->get('router')->generate('_media_edit', array('id' => $media->getId(), 'context' => $context)));
                else
                    return $this->redirect($this->get('router')->generate('_media_picker', array('id' => $media->getId(), 'context' => $context)));
            }else{
                foreach ($form->getErrors() as $error) {
                    echo $message[] = $error->getMessage();
                }
            }
        }

        $pageSubTitle = empty($media) ? $this->_translator->trans('Add a new media') : $this->_translator->trans('Edit media') . ' ' . $media->getTitle();


        return $this->render('MajesMediaBundle:Admin:edit.html.twig', array(
            'pageTitle' => $this->_translator->trans('Media management'),
            'pageSubTitle' => $pageSubTitle,
            'context' => $context,
            'form' => $form->createView()));
    }

    /**
     * @Secure(roles="ROLE_MEDIA_EDIT,ROLE_SUPERADMIN")
     *
     */
    public function multipleEditAction($context)
    {
        $request = $this->getRequest();

        $em = $this->getDoctrine()->getManager();
        $media = null;

        if($request->isXmlHttpRequest()){

                if(is_null($media)){

                    $media = new Media();
                    $media->setCreateDate(new \DateTime(date('Y-m-d H:i:s')));
                    $media->setUser($this->_user);
                    $media->setFile($request->files->get('file'));
                    $media->setTitle($request->files->get('file')->getClientOriginalName());
                    $media->setTypeByMime($request->files->get('file')->getMimeType());
                    $folder = $request->request->get('folder');
                    if(!empty($folder))
                        $media->setFolder($folder);
                    else
                        $media->setFolder('Cms');
                    if(!is_null($request->request->get('protected')))
                        $media->setIsProtected($request->request->get('protected'));

                }

                $em = $this->getDoctrine()->getManager();

                $em->persist($media);
                $em->flush();

                return new Response(json_encode(array('status' => 200)));

        }

        $pageSubTitle = empty($media) ? $this->_translator->trans('Add a new media') : $this->_translator->trans('Edit media') . ' ' . $media->getTitle();


        return $this->render('MajesMediaBundle:Admin:multipleEdit.html.twig', array(
            'pageTitle' => $this->_translator->trans('Media management'),
            'pageSubTitle' => $pageSubTitle,
            'context' => $context,
            // 'form' => $form->createView()
            ));
    }

    /**
     * @Secure(roles="ROLE_MEDIA_REMOVE,ROLE_SUPERADMIN")
     *
     */
    public function deleteAction($id, $context){
        $request = $this->getRequest();

        $em = $this->getDoctrine()->getManager();
        $media = $em->getRepository('MajesMediaBundle:Media')
            ->findOneById($id);

        if(!is_null($media)){
            $em->remove($media);
            $em->flush();
        }


        if($context == 'full')
            return $this->redirect($this->get('router')->generate('_media_list', array('context' => $context)));
        else
            return $this->redirect($this->get('router')->generate('_media_picker', array('context' => $context)));
    }

    /**
     * @Secure(roles="ROLE_MEDIA_LIST,ROLE_SUPERADMIN")
     *
     */
    public function mediapickerAction($context){

        $_results_per_page = 20;

        $request = $this->getRequest();

        $types = $request->get('types');
        $folders = $request->get('folders');
        $page = $request->get('page');
        $ref = $request->get('ref', null);

        $loadmore = false;

        $em = $this->getDoctrine()->getManager();

        if(!is_null($types) && in_array('', $types)) $types = null;
        if(!is_null($folders) && in_array('', $folders)) $folders = null;
        if(is_null($page)) $page = 1;

        if(!is_null($types) || !is_null($folders)){
            $medias = $em->getRepository('MajesMediaBundle:Media')
                ->findForAdmin($types, $folders, $page, $_results_per_page);
        }else{
            $medias = $em->getRepository('MajesMediaBundle:Media')
                ->findForAdmin(null, null, $page, $_results_per_page);
        }

        $loadmore = count($medias) > $_results_per_page ? true : false;
        count($medias) > $_results_per_page ? array_pop($medias) : $medias;

        //Get all folders
        $all_folders =  $em->getRepository('MajesMediaBundle:Media')->listFolders();

        if($request->isXmlHttpRequest()){
            return $this->render('MajesMediaBundle:Admin:ajax/list-results.html.twig', array(
                'medias' => $medias,
                'loadmore' => $loadmore,
                'all_folders' => $all_folders,
                'page' => $page,
                'context' => $context,
                'ref' => $ref
                ));
        }else
            return $this->render('MajesMediaBundle:Admin:mediapicker.html.twig', array(
                'pageTitle' => $this->_translator->trans('Media management'),
                'pageSubTitle' => $this->_translator->trans('List of all media created'),
                'medias' => $medias,
                'folders' => $folders,
                'types' => $types,
                'loadmore' => $loadmore,
                'all_folders' => $all_folders,
                'page' => $page,
                'context' => $context,
                'ref' => $ref
                ));




    }


}
