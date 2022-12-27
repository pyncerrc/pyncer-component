<?php
namespace Pyncer\Component\Page;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Component\Element\AbstractElement;
use Pyncer\Component\Page\PageComponentInterface;

use function Pyncer\IO\clean_dir as pyncer_io_clean_dir;

abstract class AbstractPage extends AbstractElement implements
    PageComponentInterface
{
    protected ?string $dir;
    protected array $paths;

    public function __construct(
        PsrServerRequestInterface $request,
        ?string $dir,
        array $paths,
    ) {
        parent::__construct($request);

        $this->dir = ($dir !== null ? pyncer_io_clean_dir($dir) : $dir);
        $this->paths = $paths;
    }

    protected function isValidRequest(): bool
    {
        if (!$this->isValidPath()) {
            return false;
        }

        return parent::isValidRequest();
    }

    protected function isValidPath(): bool
    {
        if ($this->paths) {
            return false;
        }

        return true;
    }
}
