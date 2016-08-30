<?php

namespace Gedmo\Translatable\Entity;

use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\Entity;

/**
 * Gedmo\Translatable\Entity\Translation
 *
 * @Table(
 *         name="ext_translations",
 *         indexes={@index(name="translations_lookup_idx", columns={
 *             "locale", "object_class", "foreign_key"
 *         })},
 *         uniqueConstraints={@UniqueConstraint(name="lookup_unique_idx", columns={
 *             "locale", "object_class", "foreign_key", "field"
 *         })}
 * )
 * @Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class Translation extends AbstractTranslation
{
    /**
     * @var integer $id
     *
     * @Column(type="integer")
     * @Id
     * @GeneratedValue
     */
// Fix/Customizing: $translationRepo->translate() won't work otherwise:
//  Fatal error: Cannot access private property Gedmo\Translatable\Entity\Translation::$id
//  in C:\contrexx\c_vbv\lib\doctrine\Gedmo\Translatable\Entity\AbstractTranslation.php on line 59
// was:
//    private $id;
    public $id;

    /**
     * All required columns are mapped through inherited superclass
     */
}