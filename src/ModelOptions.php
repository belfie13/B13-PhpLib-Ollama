<?php

namespace B13\Ollama;

class ModelOptions
{
    /**
     * 
     */
    public int      $num_keep;
    /**
     * 
     */
    public int      $seed;
    /**
     * 
     */
    public int      $num_predict;
    /**
     * 
     */
    public int      $top_k;
    /**
     * 
     */
    public float    $top_p;
    /**
     * 
     */
    public float    $min_p;
    /**
     * 
     */
    public float    $typical_p;
    /**
     * 
     */
    public int      $repeat_last_n;
    /**
     * 
     */
    public float    $temperature;
    /**
     * 
     */
    public float    $repeat_penalty;
    /**
     * 
     */
    public float    $presence_penalty;
    /**
     * 
     */
    public float    $frequency_penalty;
    /**
     * 
     */
    public int      $mirostat;
    /**
     * 
     */
    public float    $mirostat_tau;
    /**
     * 
     */
    public float    $mirostat_eta;
    /**
     * 
     */
    public bool     $penalize_newline;
    /**
     * 
     */
    public array    $stop;
    /**
     * 
     */
    public bool     $numa;
    /**
     * 
     */
    public int      $num_ctx;
    /**
     * 
     */
    public int      $num_batch;
    /**
     * 
     */
    public int      $num_gpu;
    /**
     * 
     */
    public int      $main_gpu;
    /**
     * 
     */
    public bool     $low_vram;
    /**
     * 
     */
    public bool     $vocab_only;
    /**
     * 
     */
    public bool     $use_mmap;
    /**
     * 
     */
    public bool     $use_mlock;
    /**
     * 
     */
    public int      $num_thread;
    /**
     * 
     */
    public function toArray(): array
    {
        return (array)$this;
    }
    /**
     * 
     */
    public function toJson(): string
    {
        return json_encode($this);
    }
    /**
     * A string representation of parameters for use in an Ollama modelfile.
     */
    public function toModelFile(): string
    {
        $ret = "";
        foreach ($this->toArray() as $name => $value)
        {
            $ret.= "PARAMETER $name $value\n";
        }
        return $ret;
    }
}
