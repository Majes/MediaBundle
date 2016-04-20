<?php
namespace Majes\MediaBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

use Majes\CoreBundle\Annotation\DataTable;
use Majes\CoreBundle\Entity\User\User;


/**
 * @ORM\Entity(repositoryClass="Majes\MediaBundle\Entity\MediaRepository")
 * @ORM\Table(name="media", indexes={
 *      @ORM\Index(name="external_id", columns={"external_id"}),
 *      @ORM\Index(name="type", columns={"type"}),
 *      @ORM\Index(name="folder", columns={"folder"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class Media
{

    private $file_temp;
    private $path_temp;
    private $folder_temp;

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    private $collect;

    /**
     * @ORM\ManyToOne(targetEntity="Majes\CoreBundle\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $user=null;

    /**
     * @ORM\Column(name="type", type="string", length=100, nullable=false)
     */
    private $type='picture';

    /**
     * @ORM\Column(name="folder", type="string", length=100, nullable=false)
     */
    private $folder='default';

    /**
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title=null;

    /**
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    private $author=null;

    /**
     * @ORM\Column(name="path", type="string", length=255, nullable=true)
     */
    private $path=null;

    /**
     * @ORM\Column(name="realpath", type="string", length=255, nullable=true)
     */
    private $realpath=null;

    private $pathForced=null;

    private $file;

    /**
     * @ORM\Column(name="embedded", type="text", nullable=true)
     */
    private $embedded;

    /**
     * @ORM\Column(name="is_protected", type="boolean", nullable=false)
     */
    private $isProtected=0;


    /**
     * @ORM\Column(name="create_date", type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @ORM\Column(name="update_date", type="datetime", nullable=false)
     */
    private $updateDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="external_id", type="integer", nullable=true)
     */
    private $externalId;

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
    * @inheritDoc
    */
    public function getCollect()
    {
        return $this;
    }

    /**
    * @inheritDoc
    */
    public function getCollected()
    {
        return $this->collect;
    }

    /**
    * @inheritDoc
    */
    public function setCollect($collect)
    {
        $this->collect = $collect;
        return $this;
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
    public function getRealpath()
    {
        return $this->realpath;
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
    public function getExternalId()
    {
        return $this->externalId;
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
    public function setTypeByMime($mimeType)
    {
        $mimeType = explode('/', $mimeType);
        $type = 'picture';
        switch ($mimeType[0]) {
            case 'image':
                $type = 'picture';
                break;

            case 'video':
                $type = 'video';
                break;

            case 'application':
                $type = 'document';
                break;

            default:
                $type='picture';
                break;
        }
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
    public function setFile($file = null)
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
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFileFromUrl($url, $realpath = null, $copy = false)
    {
        $tempId = uniqid();
        $rootDir = __DIR__.'/../../../../../../web';
        $today = date('dmY');

        if (!file_exists($rootDir.'/temp/'.$today)){
            if (!mkdir($rootDir.'/temp/'.$today, 0755, true))
                return false;
        }

        $path = $rootDir.'/temp/'.$today.'/'.$tempId.'.'.pathinfo($url, PATHINFO_EXTENSION);
        //$this->tempPath = $path;

        if($copy && file_exists($url)){
            copy($url, $path);
        }
        else{
            if(!file_put_contents($path, file_get_contents($url)))
                return false;
        }

        $pathinfo = pathinfo($path);
        $basename = $pathinfo['basename'];

        try {
            $uploadedFile = new File($path);
        } catch (FileNotFoundException $ex) {
            throw new TransformationFailedException($ex->getMessage());
        }

        if(!is_null($realpath)) $this->realpath = $realpath;

        $this->setFile($uploadedFile);

        //unlink($path);
        //rmdir($rootDir.'/temp/'.$today);
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
    public function setRealpath($realpath)
    {
        $this->realpath = $realpath;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setPathForced($pathForced)
    {
        $this->pathForced = $pathForced;
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
     * @inheritDoc
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
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
            $this->path = empty($this->pathForced) ? $filename.'.'.$this->getFile()->guessExtension() : $this->pathForced;
            $file = $this->getFile();
            if($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile){
                $this->realpath = is_null($this->realpath) ? $this->getFile()->getClientOriginalName() : $this->realpath;
            }

        }
    }

    /**
    * @ORM\PostLoad
    */
    public function settingTemp(){
        $this->file_temp = $this->file;
        $this->path_temp = $this->path;
        $this->folder_temp = $this->folder;
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {

        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            $old = str_replace($this->folder,$this->folder_temp,$this->getUploadRootDir());
            if(is_dir($old))
                rename($old,$this->getUploadRootDir());
            return;
        }


        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->getFile()->move($this->getUploadRootDir(), $this->path);

        $isset = $this->getFile()->getPathname();
        //if(is_file($isset))
        //    unlink($isset);

        // check if we have an old image
        if (isset($this->file_temp) && is_file($this->getUploadRootDir().'/'.$this->file_temp)) {
            // delete the old image
            unlink($this->getUploadRootDir().'/'.$this->file_temp);
            $crop_temps = glob($this->getUploadRootDir().'/cache/*'); // get all file names
            foreach($crop_temps as $crop_temp){
              if(is_file($crop_temp))
                unlink($crop_temp);
            }
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
        if($this->getIsProtected() == 0)
            $this->path_temp = __DIR__.'/../../../../../../web/'.$this->getUploadDir().'/'.$this->getCreateDate()->format('Y-m-d').'/'.$this->getId();
        else
            $this->path_temp = __DIR__.'/../../../../../../app/private/'.$this->getUploadDir().'/'.$this->getCreateDate()->format('Y-m-d').'/'.$this->getId();
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

    public function getWebCacheFolder()
    {

        $subfolder = $this->getCreateDate()->format('Y-m-d').'/'.$this->getId().'/cache';

        return null === $this->path
            ? null
            : $this->getUploadDir().'/'.$subfolder.'/';
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        if($this->getIsProtected() == 0)
            $folder = __DIR__.'/../../../../../../web/'.$this->getUploadDir();
        else{
            if(!is_dir(__DIR__.'/../../../../../../app/private'))
                mkdir(__DIR__.'/../../../../../../app/private', 0775);

            if(!is_dir(__DIR__.'/../../../../../../app/private/media'))
                mkdir(__DIR__.'/../../../../../../app/private/media', 0775);

            $folder = __DIR__.'/../../../../../../app/private/'.$this->getUploadDir();
        }

        $subfolder = $this->getCreateDate()->format('Y-m-d');

        if(!is_dir($folder))
            mkdir($folder, 0755, true);

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

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdateDate(new \DateTime(date('Y-m-d H:i:s')));

        if($this->getCreateDate() == null)
        {
            $this->setCreateDate(new \DateTime(date('Y-m-d H:i:s')));
        }
    }

}
