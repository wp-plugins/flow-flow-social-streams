<?php if ( ! defined( 'WPINC' ) ) die;
/**
 * Flow-Flow
 *
 * @package   FlowFlow
 * @author    Looks Awesome <email@looks-awesome.com>

 * @link      http://looks-awesome.com
 * @copyright 2014 Looks Awesome
 */

interface FFFeed {
    public function id();
    public function init($options, $stream, $feed);
    public function posts();
    public function errors();
    public function useCache();
}