<?php
namespace CPTHelper;
/**
 * Class CptHelper - represents a custom post type.
 * You can also add custom fields for the post type, using addField(new FieldHelper()) and related classes.
 * You can extend this class to provide a standard prefix and further modify behaviour
 * There are a lot of methods apart from addField to modify the behaviour. They all return $this to allow chaining.
 *
 * Compared to just using register_post_type() directly it doesnt add much value, but it is much more useful when you also add fields.
 * It is just one more line to add a new field which automatically gets added to the meta box for the post and gets saved for you.
 *
 * Late in the project, I added instance classes too, which feed off the CptHelper to get info about their custom data values.
 * There are currently a lot of functions in GSG related to specific post types which would have been better written on their instance classes.
 */

/*
 * Sample usage (GsgCpt) is a child class that just sets a prefix):
       $case = new GsgCpt("casestudy","Case Study","Case Studies",[],__FILE__);
       $case->urlSlug('case_studies')
           ->addField(new FieldHelper("post-class","Post-specific CSS classes","The class will be added to post and to links. Can be multiple, separated by spaces."))
           ->addField(new PostSelector("author_contact","Author contact or organisation","The real author, if a known contact in the system",["posttypes"=>["gsg_contact","gsg_nab"]]))
           ->addField(new FieldHelper("actual_author","Actual Case Study Author","The real author, if not a contact in the system"))
           ->addField(new MediaSelector2("author_image","Author Photo","If not a contact, upload the image to the media library first, then refer to it here."))
           ->addField(new FieldHelper("video_url","Video URL","Works for Youtube and Vimeo hosted videos - include the entire URL. We dont host videos on our own server."))
           ->addField(new FieldHelper("sub-heading","Subheading under the main header","This appears just below the white box on the page."))
           ->addField(new FieldHelper("title-short","Short version of the title","The default will be an abbreviation to ".$this->title_chars_short." chars"))
           ->addField(new FieldHelper("title-very-short","Very short version of the title","The default will be an abbreviation to ".$this->title_chars_very_short." chars"))
           ->addField(new GSGStatistic("statistics","Stand-out Statistics","The most important statistics, highlighted for the public"))
           ->addField(new MediaSelector2("casestudy_logo","Logo for the case study","If there is one, it will be shown below the hero image."))
           ->addField(new FieldHelper("sequence","Sequence","Used to infuence the order that posts, pages etc are shown.".$seqdesc))
           ->addField(new CPTSelectHelper("nabowner","Owning NAB","Which NAB 'owns' - is responsible for the content of - this item.",["posttype"=>"gsg_nab"]))
           ->allowComments()
           ->allowExcerpt();

 */
class CptHelper {

    // class variable to keep track of them all
    static protected $registrations = [];

    protected $slug;
    protected $labels;
    protected $options;
    protected $prefix = "cpth_";
    protected $urlslug = null;
    protected $showInQueries = false;
    protected $supports = [];
    protected $metaFields = [];
    protected $constants = [];
    protected $instanceClass = 'CPost';
    protected $flushRules = false;
    /**
    * Is the post type built-in, or created somewhere else.
    */
    protected $builtin = false;

    public function __construct($slug,$name,$plural,$optionslist,$pluginfile=null){
        // builtin call is for adding a metabox to an existing type.
        $this->slug = $slug;

        $this->builtin = ($name===null);
        if (!$this->builtin){
            if (TRACEIT) traceit("NEW CPT ".$slug);
            $this->labels = ['name'=>$plural, 'singular_name'=>$name,
              'add_new'=>"Add new ".$name,
              'add_new_item'=>"Add new ".$name,
              'edit_item'=>"Edit ".$name,
              'new_item'=>"Add new ".$name,
              'view_item'=>"View ".$name,
              'search_items'=>"Search ".$name,
              ];
            $this->options = $optionslist;
            $this->supports = (isset($optionslist["supports"])) ? $optionslist["supports"] : ["title", "editor", "thumbnail", "author"];
            $this->setup();

            if ($pluginfile){
              //if (TRACEIT) traceit("!!!!!!!registering hook for file ".$pluginfile);
              register_activation_hook( $pluginfile, [$this,'flush'] );
              register_deactivation_hook( $pluginfile, [$this,'flush'] );
            }
        }
        add_action( 'init', [$this,'register'] );

    }

    /**
     * This function is present to allow child classes to set up other things.
     */
    protected function setup(){

    }
    /**
     * Fired on plugin activation or deactivation
     * This mechanism doesnt work - it will still be necessary to manually flush in Settings/Permalinks.
     * During plugin activation, this class is instantiated twice - the plugin activation is separate from the one that registers the CPTs,
     *   so this flag never actually gets used.
     * I am leaving the logic here in the hope that i come up with a solution.
     */
    public function flush(){
        // if (TRACEIT) traceit("!!!!!!Setting the flush signal on activation");
        $this->flushRules = true;
    }
    public function register(){
        if (!$this->builtin){
            $optslist = array_merge($this->deflt(),$this->options);
            $optslist['labels'] = $this->labels;

            if ($this->urlslug) {
                $optslist['rewrite']['slug'] = $this->urlslug;
                //$optslist['rewrite']['ep_mask'] = EP_PERMALINK;
                $optslist['rewrite']['with_front'] = false;
            }
            if ($this->supports){
                $optslist["supports"] = $this->supports;
            }
            if ($this->metaFields) $optslist["supports"][] = 'custom_fields';

            if (TRACEIT) traceit("!!!!!!!!actually registering the post type ".print_r($optslist,true));
            $cpt = register_post_type($this->posttype(), $optslist);
            self::$registrations[$this->posttype()] = $this;

            if ($this->showInQueries){
                add_action( 'pre_get_posts', [$this,'add_to_query'] );
            }

            if ($this->flushRules) {
                //if (TRACEIT) traceit("!!!!!!!!!!!!!!Flushing rules on plugin activation after CPT registration");
                flush_rewrite_rules();   // only on plugin (de)activation
            }
        }
        if ($this->metaFields){
            add_action( 'add_meta_boxes', [$this, 'addMetaBox'] );

            //add_action('save_post',[$this, 'saveMetaBox'], 1,2);
            add_action('pre_post_update',[$this, 'saveMetaBox'], 1,2);

            add_action( 'admin_enqueue_scripts', [$this,'enqueueStyle'] );
            add_action( 'admin_init', [$this,'admin_init'] );

        }
    }
    public function admin_init(){
        foreach($this->metaFields as $field) $field->admin_init();

        // We dont need this for the metabox, only if the child class wants to do some more
        if (method_exists($this,"on_save")){
          add_action('save_post',[$this, 'further_save'], 1,2);
        }
    }
    protected function globaldefault(){
        return [
            'has_archive'         => true,
            'public'              => true,
        		'publicly_queryable'  => true,
        ];
    }
    public function enqueueStyle(){
        wp_enqueue_style("cptcss", plugins_url( 'css/CptHelper.css', dirname(__FILE__) ), [], "1.0");
    }
    public function posttype(){
        return $this->builtin ? $this->slug : $this->prefix.$this->slug;
    }
    public function further_save($post_id, $post){
        if ($post->post_type != $this->posttype()) return;
        $this->on_save($post_id, $post);
    }
    // TODO got to get the current list and add ours to it
    public function add_to_query($query){
        if ( is_home() && $query->is_main_query() ){
            $query->set( 'post_type', array( 'post', $this->posttype() ) );
        }
         return $query;
    }

    /**
     * This is another step to allow for overriding by child class.
     * @return array
     */
    public function deflt(){
        return $this->globaldefault();
    }
    public function addMetaBox(){
        $id = $this->slug."_mbid";
        $title = $this->builtin ? "Additional Information" : $this->labels['singular_name']." Information";
        $context = 'normal';    // show box with other post data
        $priority = 'high';     // put it high up the page
        if (TRACEIT) traceit("actually adding the box with id ".$id);
        //add_meta_box( $id, $title, [$this, 'writeMetaBox'], 'post', $context, $priority );
        add_meta_box( $id, $title, [$this, 'writeMetaBox'], $this->posttype(), $context, $priority );
    }
    public function noncefield(){
        return $this->slug."_nonce";
    }
    public function writeMetaBox()
    {
        $this->tracePost();
        //$noncefield = $this->slug."_nonce";
        //if (TRACEIT) traceit("Nonce field retrieved is ".$_REQUEST[$noncefield]." data=".$_REQUEST['investment_value']);

        /*
        if (wp_verify_nonce( $_REQUEST[$noncefield], plugin_basename( __FILE__ ))) {
            if (TRACEIT) traceit("Nonce has successfulyl verified for ".$noncefield);
            foreach($this->metaFields as $field) $field->update();
        } else if (TRACEIT) traceit("Nonce not verified for ".$noncefield);
        if (TRACEIT) traceit("Nonce written for ".$noncefield);
        */
        wp_nonce_field( plugin_basename( __FILE__ ), $this->noncefield() );
        foreach($this->metaFields as $field) echo $field->html();
    }
    public function saveMetaBox($post_id)
    {
        if (TRACEIT) traceit("=================== Save MetaBox has been triggered post=".$post_id);
        if (!wp_verify_nonce( $_REQUEST[$this->noncefield()], plugin_basename( __FILE__ ))) return;

        if (TRACEIT) traceit("=================== nonce is good");
        foreach($this->metaFields as $field) $field->update($post_id);

        // constant values
        foreach($this->constants as $constant){
            update_post_meta( $post_id, $constant[0], $constant[1] );
        }
    }
    /**
     * Set the slug in the URL to something prettier than the actual type.
     * Returns $this to allow chaining.
     * @param $slug
     * @return $this
     */
    public function urlSlug($slug){
        $this->urlslug = $slug;
        return $this;
    }

    /**
     * Call this function straight after object creation to add the CPT to general queries
     * Returns $this to allow chaining.
     */
    public function addToQueries(){
        $this->showInQueries = true;
        return $this;
    }

    /**
     * Allow comments on this CPT by adding to the supports field value.
     * @return $this
     */
    public function allowComments(){
        $this->supports[] = "comments";
        return $this;
    }
    public function allowExcerpt(){
        $this->supports[] = "excerpt";
        return $this;
    }
    public function addField($field)
    {
        $this->metaFields[] = $field;
        return $this;
    }
    /**
    * Add a constant custom field value that should always be set.
    * If a query includes several CPTs then it may have a predicate which isnt applicable to all of them. This gets around that.
    */
    public function addConstant($name,$value){
        $this->constants[] = [$name,$value];
        return $this;
    }
    protected function tracePost(){
        global $post;
        if (TRACEIT) traceit( ($post) ? "POST-".$post->ID : "No Post");
    }

    /**
     * Static function which uses a class static variable which stores all of the CptHelpers indexed by the post type.
     * @param $slug
     * @return mixed|null
     */
    static function get($slug){
        return isset(self::$registrations[$slug]) ? self::$registrations[$slug] : null;
    }
}

?>
