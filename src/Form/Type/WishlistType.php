<?php

declare(strict_types=1);

namespace Webburza\SyliusWishlistPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WishlistType extends AbstractResourceType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) : void
    {
        $builder->add('title', Type\TextType::class, [
            'label'       => 'sylius.ui.title',
            'required'    => true,
            'constraints' => [
                new NotBlank()
            ]
        ]);

        $builder->add('description', Type\TextareaType::class, [
            'label'    => 'sylius.ui.description',
            'required' => false
        ]);

        $builder->add('public', Type\CheckboxType::class, [
            'label'    => 'webburza_sylius_wishlist.ui.public',
            'required' => false
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {

        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'validation_groups' => $this->validationGroups,
            'csrf_protection' => false
        ]);

    }

}
