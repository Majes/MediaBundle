<?php
namespace Majes\MediaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\HttpFoundation\Session\Session;

class MediaType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('file', 'file', array('required' => false, 'media_id' => 'id'));

        if ($options['title'])
            $builder->add('title', 'text', array('required' => false));

        if ($options['type']){
            $builder->add('type', 'choice', array(
                'choices' => array(
                    'picture' => 'Picture',
                    'video' => 'Video',
                    'embed' => 'Embed',
                    'document' => 'Document'
                    ),
                'required' => true));
        }

        if ($options['folder'])
            $builder->add('folder', 'text', array('required' => true));
        if ($options['embedded'])
            $builder->add('embedded', 'textarea', array('required' => false, 'label' => 'If "Embed" type selected'));
        if ($options['author'])
            $builder->add('author', 'text', array('required' => false));
        if ($options['is_protected'])
            $builder->add('is_protected', 'checkbox', array('required' => false));
    }

    public function configureOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class'      => 'Majes\MediaBundle\Entity\Media',
            'title'           => true,
            'type'            => true,
            'folder'          => true,
            'embedded'        => true,
            'author'          => true,
            'is_protected'    => true,
            'csrf_protection' => false,
        ));
    }

    public function getName() {
        return 'media';
    }
}
