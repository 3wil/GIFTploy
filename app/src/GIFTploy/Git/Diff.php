<?php

namespace GIFTploy\Git;

/**
 * Class for set up and return parsed diff.
 *
 * @author Patrik Chotěnovský
 */
class Diff
{

    /**
     * Instance of Repository.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Instance of Parser.
     *
     * @var Parser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $commitHashFrom;
    
    /**
     * @var string
     */
    protected $commitHashTo;

    /**
     * @param Repository $respository
     */
    public function __construct(Repository $respository, Parser\Parser $parser)
    {
        $this->repository = $respository;
        $this->parser = $parser;
    }

    /**
     * Returns Repository.
     *
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Sets commit hash from where to get diff.
     *
     * @param string $hash
     * @return Diff
     */
    public function setCommitHashFrom($hash)
    {
        $this->commitHashFrom = $hash;

        return $this;
    }

    /**
     * Returns starting commit hash.
     *
     * @param string
     */
    public function getCommitHashFrom()
    {
        return $this->commitHashFrom;
    }

    /**
     * Sets commit hash where to get diff.
     *
     * @param string $hash
     * @return Diff
     */
    public function setCommitHashTo($hash)
    {
        $this->commitHashTo = $hash;

        return $this;
    }

    /**
     * Returns ending commit hash.
     *
     * @param string
     */
    public function getCommitHashTo()
    {
        return $this->commitHashTo;
    }

    /**
     * Returns array of files whit diff informations.
     *
     * @param array $args   Additional arguments for log command.
     * @param type $asArray Returns array if TRUE, instance of Generator otherwise,
     * @return array|Generator
     */
    public function getFiles(array $args = [], $asArray = false)
    {
        $args[] = $this->getCommitHashFrom();
        $args[] = $this->getCommitHashTo();

        $diffRaw = $this->repository->run('diff', $args);

        if ($asArray) {
            $data = [];

            foreach ($this->parser->parse($diffRaw->getOutput()) as $file) {
                $data[] = $file;
            }

            return $data;
        }

        return $this->parser->parse($diffRaw->getOutput());
    }

}
