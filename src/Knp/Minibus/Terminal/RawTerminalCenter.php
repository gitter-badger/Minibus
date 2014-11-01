<?php

namespace Knp\Minibus\Terminal;

use Knp\Minibus\Minibus;
use Knp\Minibus\Terminus\Terminus;
use Knp\Minibus\Exception\TerminusAlwaysExistsException;
use Knp\Minibus\Exception\TerminusNotFoundException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Knp\Minibus\Terminus\ConfigurableTerminus;

/**
 * Contains all the terminus and just return the found terminus raw data.
 *
 * @author David Jegat <david.jegat@gmail.com>
 */
class RawTerminalCenter implements TerminalCenter
{
    /**
     * @var Terminus[] $terminals
     */
    private $terminals;

    /**
     * @var Processor $processor
     */
    private $processor;

    /**
     * @var TreeBuilder $builder
     */
    private $builder;

    /**
     * @param Processor $processor
     * @param TreeBuilder $builder
     */
    public function __construct(
        Processor $processor = null,
        TreeBuilder $builder = null
    ) {
        $this->terminals = [];
        $this->processor = $processor ?: new Processor;
        $this->builder   = $builder ?: new TreeBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function addTerminus($name, Terminus $terminus)
    {
        if (isset($this->terminals[$name])) {
            throw new TerminusAlwaysExistsException(sprintf(
                'the terminus %s is always present in the terminal center :-(',
                $name
            ));
        }

        $this->terminals[$name] = $terminus;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Minibus $minibus, $name, array $configuration = [])
    {
        if (!isset($this->terminals[$name])) {
            throw new TerminusNotFoundException(sprintf(
                'The terminus %s does not seems to be registered inside the terminal center :-(',
                $name
            ));
        }

        $terminus = $this->terminals[$name];

        if ($terminus instanceof ConfigurableTerminus) {
            $terminus->configure($this->builder->root($name));
            $configuration = $this->processor->process($this->builder->buildTree(), [$configuration]);
        }

        return $terminus->terminate($minibus, $configuration);
    }
}
