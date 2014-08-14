<?php
namespace AC\Models\Interfaces;

interface Paginator
{
    /**
     * @return array
     */
    public function toArray();

    /**
     * @return $this
     */
    public function setNextPage();

    /**
     * @return bool
     */
    public function canPaginateFurther();

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @param $mixed
     * @return $this
     * @throws \RuntimeException
     */
    public function setResponse($mixed);
}