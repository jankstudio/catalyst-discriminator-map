<?php

namespace Catalyst\DiscriminatorMapBundle\Service;

use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Annotations\AnnotationReader;

class DoctrineEventListener
{
    protected $container;
    protected $data;
    protected $resources;

    public function __construct($container)
    {
        $this->container = $container;
        $this->data = [];
        $this->resources = [];
        $this->populateData();
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $metadata = $event->getClassMetadata();
        $class = $metadata->getReflectionClass();

        if ($class === null) {
            $class = new \ReflectionClass($metadata->getName());
        }

        // check if we have it in our list
        $entity_name = $class->getName();
        if (isset($this->data[$entity_name]))
            $metadata->setDiscriminatorMap($this->data[$entity_name]);
    }

    public function populateData()
    {
        $kernel = $this->container->get('kernel');

        // cache
        $cache_dir = $kernel->getCacheDir();
        $cache_file = $cache_dir . '/dmap.serial';
        $data_cache = new ConfigCache($cache_file, true);

        if (!$data_cache->isFresh())
        {
            $this->resources = [];

            // add appKernel to handle new bundles being added
            $app_kernel_path = $kernel->getRootDir() . '/AppKernel.php';
            $this->resources[] = new FileResource($app_kernel_path);

            // parse
            $this->parseAllEntries();

            // serialize
            $data_serial = serialize($this->data);
            $data_cache->write($data_serial, $this->resources);
        }
        else
        {
            $data_serial = file_get_contents($cache_file);
            $this->data = unserialize($data_serial);
        }
    }

    protected function parseAllEntries()
    {
        $kernel = $this->container->get('kernel');
        $bundles = $this->container->getParameter('kernel.bundles');
        foreach ($bundles as $name => $class)
        {
            try
            {
                // NOTE: currently only parses yml files
                $path = $kernel->locateResource('@' . $name . '/Resources/config/dmap.yml');

                // add to resources and parse
                $this->resources[] = new FileResource($path);
                $this->parseEntities($path);
            }
            catch (\InvalidArgumentException $e)
            {
            }
        }
    }

    protected function parseEntities($path)
    {
        $parser = new YamlParser();
        $config_data = $parser->parse(file_get_contents($path));

        foreach ($config_data['entities'] as $entity)
        {
            $parent = $entity['parent'];

            if (!isset($this->data[$parent]))
                $this->data[$parent] = [];

            foreach ($entity['children'] as $child)
            {
                $this->data[$parent][$child['id']] = $child['class'];
            }
        }
    }
}
