<?php

namespace Blogger\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Blogger\BlogBundle\Entity\Enquiry;
use Blogger\BlogBundle\Form\EnquiryType;

class PageController extends Controller
{
    public function indexAction()
    {
        //return $this->render('BloggerBlogBundle:Page:index.html.twig');
        
        $em = $this->getDoctrine()
                   ->getManager();

//Se mueve el codigo a BlogRepository, al no ser este su mejor lugar, razones:
//-Nos gustaría volver a utilizar la consulta en otras partes de la aplicación, sin necesidad de duplicar el código del QueryBuilder.
//-Si duplicamos el código del QueryBuilder, tendríamos que hacer varias modificaciones en el futuro si fuera necesario cambiar la consulta.
//-La separación de la consulta y el controlador nos permitirá probar la consulta de forma independiente del controlador.
//Doctrine 2 proporciona las clases Repositorio para facilitarnos esta tarea.
//              
//        $blogs = $em->createQueryBuilder()
//                    ->select('b')
//                    ->from('BloggerBlogBundle:Blog',  'b')
//                    ->addOrderBy('b.created', 'DESC')
//                    ->getQuery()
//                    ->getResult();
        $blogs = $em->getRepository('BloggerBlogBundle:Blog')
                    ->getLatestBlogs(5);        

        return $this->render('BloggerBlogBundle:Page:index.html.twig'
                            , array('blogs' => $blogs));        
    }
    
    public function aboutAction()
    {
        return $this->render('BloggerBlogBundle:Page:about.html.twig');
    }
    
    public function contactAction()
    {        
        $enquiry = new Enquiry();
        $form = $this->createForm(new EnquiryType(), $enquiry);
        //$form = $this->get('form.factory')->create(new EnquiryType(),array());

        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);
            
            if ($form->isValid()) {

                $message = \Swift_Message::newInstance()
                    ->setSubject('Contact enquiry from symblog')
                    ->setFrom('enquiries@symblog.co.uk')
                    //->setTo('contacte@email.com')
                    ->setTo($this->container->getParameter('blogger_blog.emails.contact_email'))
                    ->setBody(
                        $this->renderView(
                            'BloggerBlogBundle:Page:contactEmail.txt.twig',
                            array('enquiry' => $enquiry)
                        )
                      );
                $this->get('mailer')->send($message);

                $this->get('session')->setFlash('blogger-notice', 'Your contact enquiry was successfully sent. Thank you!');

                // Redirect - This is important to prevent users re-posting
                // the form if they refresh the page
                return $this->redirect($this->generateUrl('BloggerBlogBundle_contact'));
            }
        }

        return $this->render('BloggerBlogBundle:Page:contact.html.twig', array(
            'form' => $form->createView()
        ));   
    }    

    public function sidebarAction()
    {
        $em = $this->getDoctrine()
                   ->getManager();

        $tags = $em->getRepository('BloggerBlogBundle:Blog')
                   ->getTags();

        $tagWeights = $em->getRepository('BloggerBlogBundle:Blog')
                         ->getTagWeights($tags);

        return $this->render('BloggerBlogBundle:Page:sidebar.html.twig', array(
            'tags' => $tagWeights
        ));
    }    
}
