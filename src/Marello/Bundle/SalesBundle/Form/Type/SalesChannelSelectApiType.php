<?php

namespace Marello\Bundle\SalesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;

class SalesChannelSelectApiType extends AbstractType
{
    const NAME = 'marello_sales_channel_select_api';

    /** @var DataTransformerInterface */
    protected $transformer;

    /**
     * SalesChannelSelectApiType constructor.
     *
     * @param DataTransformerInterface $transformer
     */
    public function __construct(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addModelTransformer($this->transformer);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return self::NAME;
    }

    public function getParent()
    {
        return 'text';
    }
}
