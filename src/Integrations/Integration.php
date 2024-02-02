<?php

namespace dbsnOOp\Integrations;

abstract class Integration
{
    
    protected string $integration_name;
    protected string $integration_class;

    /**
     * 
     */
    abstract public function integrate();

}