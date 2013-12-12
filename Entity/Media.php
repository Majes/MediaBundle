<?php
namespace Majes\MediaBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Majes\CoreBundle\Annotation\DataTable;
use Majes\CoreBundle\Entity\User\User;


/**
 * @ORM\Entity(repositoryClass="Majes\MediaBundle\Entity\MediaRepository")
 * @ORM\Table(name="media")
 * @ORM\HasLifecycleCallbacks
 */
class Media
{

    private $file_temp;
    private $path_temp;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Majes\CoreBundle\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(name="type", type="string", length=100)
     */
    private $type;

    /**
     * @ORM\Column(name="folder", type="string", length=100)
     */
    private $folder;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(name="author", type="string", length=255)
     */
    private $author;

    /**
     * @ORM\Column(name="path", type="string", length=255)
     */
    private $path;

    private $file;

    /**
     * @ORM\Column(name="embedded", type="text")
     */
    private $embedded;

    /**
     * @ORM\Column(name="is_protected", type="boolean")
     */
    private $isProtected;


    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="create_date", type="datetime")
     */
    private $createDate;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="update_date", type="datetime")
     */
    private $updateDate;

    /**
     * @DataTable(isTranslatable=0, hasAdd=1, hasPreview=0, isDatatablejs=0)
     */
    public function __construct(){
        $this->setIsProtected(0);
        $this->setCreateDate(new \DateTime(date('Y-m-d H:i:s')));
    }

    /**
     * @inheritDoc
     * @DataTable(label="Id", column="id", isSortable=1)
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @see RoleInterface
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getEmbedded()
    {
        return $this->embedded;
    }

    /**
     * @inheritDoc
     */
    public function getIsProtected()
    {
        return $this->isProtected;
    }

    /**
     * @inheritDoc
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * @inheritDoc
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setUser(\Majes\CoreBundle\Entity\User\User $user = null)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setAuthor($author = '')
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;

        // check if we have an old image path
        if (isset($this->path)) {
            // store the old name to delete after the update
            $this->file_temp = $this->path;
            $this->path = null;
        } else {
            $this->path = 'initial';
        }
    }

    /**
     * @inheritDoc
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setEmbedded($embedded)
    {
        $this->embedded = $embedded;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setIsProtected($isProtected)
    {
        $this->isProtected = $isProtected;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;
        return $this;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->getFile()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->path = $filename.'.'.$this->getFile()->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }
    
        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->getFile()->move($this->getUploadRootDir(), $this->path);

        // check if we have an old image
        if (isset($this->file_temp)) {
            // delete the old image
            unlink($this->getUploadRootDir().'/'.$this->file_temp);
            // clear the temp image path
            $this->file_temp = null;
        }
        $this->file = null;
    }

    /**
     * @ORM\PreRemove()
     */
    public function storeFilenameForRemove()
    {
        $this->file_temp = $this->getAbsolutePath();
        $this->path_temp = __DIR__.'/../../../../../../web/'.$this->getUploadDir().'/'.$this->getCreateDate()->format('Y-m-d').'/'.$this->getId();
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if (isset($this->file_temp)) {
            if(is_file($this->file_temp)) unlink($this->file_temp);
            $this->rrmdir($this->path_temp);
        }
    }


    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getCachePath()
    {
        if(!is_dir($this->getUploadRootDir().'/cache/'))
            mkdir($this->getUploadRootDir().'/cache/', 0755);

        return null === $this->path
            ? null
            : $this->getUploadRootDir().'/cache/';
    }

    public function getWebPath()
    {

        $subfolder = $this->getCreateDate()->format('Y-m-d').'/'.$this->getId();

        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$subfolder.'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        $folder = __DIR__.'/../../../../../../web/'.$this->getUploadDir();

        $subfolder = $this->getCreateDate()->format('Y-m-d');

        if(!is_dir($folder))
            mkdir($folder, 0755);

        $folder = $folder.'/'.$subfolder;
        if(!is_dir($folder))
            mkdir($folder, 0755);

        $folder = $folder.'/'.$this->getId();
        if(!is_dir($folder))
            mkdir($folder, 0755);

        return $folder;
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'media/'.$this->getFolder();
    }


    /*Remove a dir*/
    protected function rrmdir($dir) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file))
                $this->rrmdir($file);
            else
                unlink($file);
        }
        rmdir($dir);
    }



}