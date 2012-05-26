<?php

namespace Liip\ImagineBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

class ImagineController
{
    /**
     * @var DataManager
     */
    private $dataManager;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * Constructor
     *
     * @param DataManager $dataManager
     * @param FilterManager $filterManager
     * @param CacheManager $cacheManager
     */
    public function __construct(DataManager $dataManager, FilterManager $filterManager, CacheManager $cacheManager)
    {
        $this->dataManager = $dataManager;
        $this->filterManager = $filterManager;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get the dataManager specified in construct function
     *
     * @return DataManager
     */
    public function getDataManager()
    {
        return $this->dataManager;
    }

    /**
     * Get the filterManager specified in construct function
     *
     * @return FilterManager
     */
    public function getFilterManager()
    {
        return $this->filterManager;
    }

    /**
     * Get the cacheManager specified in construct function
     *
     * @return CacheManager
     */
    public function getCacheManager()
    {
        return $this->cacheManager;
    }

    /**
     * This action applies a given filter to a given image,
     * optionally saves the image and
     * outputs it to the browser at the same time
     *
     * @param Request $request
     * @param string $path
     * @param string $filter
     *
     * @return Response
     */
    public function filterAction(Request $request, $path, $filter)
    {
        $targetPath = $this->getCacheManager()->resolve($request, $path, $filter);
        if ($targetPath instanceof Response) {
            return $targetPath;
        }

        $image = $this->getDataManager()->find($filter, $path);
        $response = $this->getFilterManager()->get($request, $filter, $image, $path);

        if ($targetPath) {
            $response = $this->getCacheManager()->store($response, $targetPath, $filter);
        }

        return $response;
    }
}
