<?php


namespace Mapbender\CoreBundle\Element;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mapbender\CoreBundle\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Templating\EngineInterface;

class ViewManagerHttpHandler
{
    /** @var EngineInterface */
    protected $templating;
    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(EngineInterface $templating, EntityManagerInterface $em)
    {
        $this->templating = $templating;
        $this->em = $em;
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     * @throws HttpException
     */
    public function handleHttpRequest(Entity\Element $element, Request $request)
    {
        switch ($request->attributes->get('action')) {
            default:
                throw new NotFoundHttpException();
            case 'listing':
                return $this->getListingResponse($element, $request);
            case 'save':
                return $this->getSaveResponse($element, $request);
        }
    }

    /**
     * @param Entity\Element $element
     * @param Request $request
     * @return Response
     */
    protected function getListingResponse(Entity\Element $element, Request $request)
    {
        $records = $this->getRepository()->findBy(array(
            'applicationSlug' => $element->getApplication()->getSlug(),
        ));
        $content = $this->templating->render('MapbenderCoreBundle:Element:view_manager-listing.html.twig', array(
            'records' => $records,
            'dateFormat' => $this->getDateFormat($request),
        ));
        return new Response($content);
    }

    protected function getSaveResponse(Entity\Element $element, Request $request)
    {
        $record = new Entity\MapViewDiff();
        // @todo: store user
        $record->setApplicationSlug($element->getApplication()->getSlug());
        $record->setTitle($request->request->get('title'));
        // NOTE: Empty arrays do not survive jQuery Ajax post, will be stripped completely from incoming data
        $record->setViewParams($request->request->get('viewParams'));
        $record->setLayersetDiffs($request->request->get('layersetsDiff', array()));
        $record->setSourceDiffs($request->request->get('sourcesDiff', array()));

        $this->em->persist($record);
        $this->em->flush();
        $content = $this->templating->render('MapbenderCoreBundle:Element:view_manager-listing-row.html.twig', array(
            'record' => $record,
            'dateFormat' => $this->getDateFormat($request),
        ));
        return new Response($content);
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getDateFormat(Request $request)
    {
        // @todo: locale-dependent format
        return 'Y-m-d H:m:i';
    }

    /**
     * @return \Doctrine\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        /** @var EntityRepository */
        $repository = $this->em->getRepository('Mapbender\CoreBundle\Entity\MapViewDiff');
        return $repository;
    }
}
