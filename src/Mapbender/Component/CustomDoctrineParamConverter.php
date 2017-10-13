<?php

namespace Mapbender\Component;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\MappingException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @deprecated: would be deleted in next release
 * @Author    : Karim Malhas <karim@malhas.de>
 * @package   : bkg
 * This Class overrides the default Doctrine ParamConverter to allow routes like this:
 * "@Route(/{/{nameaId}/foo/{namebid})"
 * @ParamConverter("namea",class="FOOBundle:A")
 * public function (A $namea)
 */
class CustomDoctrineParamConverter implements ParamConverterInterface
{

    protected $configuration;
    protected $registry = null;

    /**
     * CustomDoctrineParamConverter constructor.
     *
     * @param Registry|null $registry
     */
    public function __construct(Registry $registry = null)
    {
        if (is_null($registry)) {
            return;
        }

        $this->registry = $registry;
    }

    /**
     * @param Request        $request
     * @param ParamConverter $configuration
     * @return bool|void
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $this->configuration = $configuration;
        $class               = $configuration->getClass();
        $options             = $this->getOptions($configuration);

        // find by identifier?
        if ($request->attributes->has('id') || $request->attributes->has($configuration->getName() . "Id")) {
            $object = $this->find($class, $request, $options);
        } else {
            // $object always becomes an array here
            if (false === $object = $this->findList($class, $request, $options)) {
                throw new \LogicException('Unable to guess how to get a Doctrine instance from the request information.');
            }
        }

        if (null === $object && false === $configuration->isOptional()) {
            throw new NotFoundHttpException(sprintf('%s object not found.', $class));
        }

        $request->attributes->set($configuration->getName(), $object);

    }

    /**
     * @param ParamConverter $configuration
     * @return array
     */
    protected function getOptions(ParamConverter $configuration)
    {
        return array_replace(array(
            'entity_manager' => 'default',
        ), $configuration->getOptions());
    }

    /**
     * @param         $class
     * @param Request $request
     * @param         $options
     * @return object
     */
    protected function find($class, Request $request, $options)
    {

        // try to load by Id
        // Supports only single parameter - this is how the "normal" DoctrineParameterConverter works
        // keeping it just in case
        if ($request->attributes->has('id')) {
            return $this->registry->getRepository($class, $options['entity_manager'])->find($request->attributes->get('id'));
        }

        // try to load by classname + id
        $name = $this->configuration->getName();
        if ($request->attributes->has($name . "Id")) {
            return $this->registry->getRepository($class, $options['entity_manager'])->find($request->attributes->get($name . "Id"));
        }

    }

    /**
     * @param         $class
     * @param Request $request
     * @param         $options
     * @return array
     */
    protected function findList($class, Request $request, $options)
    {
        // if limit or offset are set we assume we the caller is trying for pagination
        // FIXME: is this clever? what if someone just wants to mess with us?

        $offset = $request->get('offset') ? $request->get('offset') : 0;
        $limit  = $request->get('limit') ? $request->get('limit') : 10;
        // allow 1000 results per page at most
        $limit = $limit < 1000 ? $limit : 1000;

        $request->attributes->set("usedOffset", $offset);
        $request->attributes->set("usedLimit", $limit);

        // this generally allows the user to search in any field
        // something else might be wanted in any case
        $criteria = array();
        if ($request->attributes->get('criteria')) {
            parse_str($request->attributes->get('criteria'), $criteria);
        }

        $result = $this->registry->getRepository($class, $options['entity_manager'])
            ->findBy($criteria, array('id' => 'ASC'), $limit + 1, $offset);
        if (count($result) === ($limit + 1)) {
            $result = array_splice($result, 0, $limit);
            $request->attributes->set('lastPage', false);
        } else {
            $request->attributes->set('lastPage', true);
        }
        return $result;
    }

    /**
     * @param ParamConverter $configuration
     * @return bool
     */
    public function supports(ParamConverter $configuration)
    {
        if (null === $this->registry) {
            return false;
        }

        if (null === $configuration->getClass()) {
            return false;
        }

        $options = $this->getOptions($configuration);

        // Doctrine Entity?
        try {
            $this->registry->getManager($options['entity_manager'])->getClassMetadata($configuration->getClass());

            return true;
        } catch (MappingException $e) {
            return false;
        }
    }
}
