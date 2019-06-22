<?php
/*
Plugin Name: GSG
Plugin URI: https://flipsidegroup.com/
Description: Specific customisations for GSG
Version: 1.4.3
Author: Derek Storkey
Author URI:
License: GPLv2 or later
Text Domain: gsgdomain
*/
// NOTE ON VERSION. Just before a release, please update the version in this plugin, the version variable below,
// in GSG Theme: version in assets/sass/style.sass (and regenerate) and the version in functions.php on the enqueue (to bypass user cache) - search for style.css in the comment.
/* TODO
NAB association dropdown. NAB association for user. placed in there automatically, based on user.
Visual composer or not?
Question - can I restrict a custom taxonomy to certain users. would be easier to do everything through editorial then.
  apparently so but i couldnt get it to work. you can high creation of new categories, but not the approval.
UPGRADE WP AND PLUGS
*/
use CPTHelper\FieldHelper;
use CPTHelper\TextAreaHelper;
use CPTHelper\SelectHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\MultiValued;
use CPTHelper\MediaSelector2;
use CPTHelper\PostSelector;
use CPTHelper\CheckBox;
use CPTHelper\SiteField;
use CPTHelper\SiteArea;

use FilterLoopPage\FieldFilter;
use FilterLoopPage\SearchFilter;
use FilterLoopPage\TaxFilter;
use FilterLoopPage\PostidFilter;
use FilterLoopPage\Controls\Radio;
use FilterLoopPage\Controls\CompactRadio;
use FilterLoopPage\Controls\FancySelect;


require_once("class/GsgCpt.php");
require_once("class/GSGStatistic.php");
require_once("class/GSGsliderInterfaces.php");

class GSG {

    // filter loop objects which may be invoked by shortcodes. DEPRECATED
    protected $loopz = [];

    // save the list of flags - it will usually be used twice, once to count them.
    protected $flags = null;

    protected $showcategories = true;

    // title abbreviations
    protected $title_chars_short = 60;
    protected $title_chars_very_short = 35;
    public $version = "v1.4.3";

    protected $pillarlist = null;

    protected $useFastPager = true;

    public $contactopt = "gsg_contact_form";    // name of the WP site option which stores the id of the contact form used for all gsg_contacts. Also referenced by the admin pages
    public $contactformid = 'contact_form_popup';    // id of the contact form, referenced on the popup buttons
    public $homeCarousel = null;

    public $userRoles = null;

    public function __construct(){
        $this->register_cpts();

        // This is needed to correct a bug in another plugin
        add_filter('rewrite_rules_array', [$this,'correctRules']);

        // adding GSG specific things to the post data
        add_filter('flp_fastpager_postinfo', [$this,'add_post_mosaic_data']);

        // init after all plugins loaded.
        add_action( 'init', [$this,'init'] );

        // when the theme header is loading
        add_action("wp_head", [$this, "wp_head"]);

        $this->homeCarousel = new MetaSliderInterface();
    }
    /**
     * Tasks for WP init phase
     */
    public function init(){
        global $post;

         $this->register_taxes();
         $this->setupFilters();

         add_filter( 'post_class', [$this,'add_class'] ,10,3 );

         if (is_admin()) $this->admin_init();

         // register footer menus
         // These will onbly be shown is template part footer-menus.php is used instead of
         register_nav_menus(
             [
                  'footer-menu-1' => __( 'Footer Menu One' ),
                  'footer-menu-2' => __( 'Footer Menu Two' ),
                  'footer-menu-3' => __( 'Footer Menu Three' ),
             ]
         );
         wp_enqueue_script("gsg_contact_popup", plugin_dir_url(__FILE__)."/js/contact-popup.js", ["jquery"], "1.0");
    }
    /**
     * Tasks for WP Admin init phase - set up the admin pages
     */
    public function admin_init(){
        require_once(dirname( __FILE__ )."/admin/GSGadmin.php");
         $adminpage = new GSGadmin($this);
         // subpages
         require_once("admin/GSGhomepage.php");
         $z = new GSGhomepage($this,$adminpage);
        require_once("admin/GSGnavmenu.php");
        $z = new GSGnavmenu($this,$adminpage);
        require_once("admin/GSGnews.php");
        $z = new GSGnews($this,$adminpage);
         require_once("admin/GSGnab.php");
         $z = new GSGnab($this,$adminpage);
         require_once("admin/GSGcase.php");
         $z = new GSGcase($this,$adminpage);
         require_once("admin/GSGreport.php");
         $z = new GSGreport($this,$adminpage);
         require_once("admin/GSGcontact.php");
         $z = new GSGcontact($this,$adminpage);
         require_once("admin/GSGevent.php");
         $z = new GSGevent($this,$adminpage);
        require_once("admin/GSGcrop.php");
        $z = new GSGcrop($this,$adminpage);
        require_once("admin/GSGvideo.php");
        $z = new GSGvideo($this,$adminpage);
        require_once("admin/GSGshort.php");
        $z = new GSGshort($this,$adminpage);
        require_once("admin/GSGusers.php");
        $z = new GSGusers($this,$adminpage);

    }
    /**
     * add post classes from the post-class custom field
     */
    public function add_class($classes, $class, $postid ){
        $extra = get_post_meta($postid, "post-class",true);
        if ($extra) $classes = array_merge($classes,explode(" ",$extra));
        return $classes;
    }
    public function updateRoles(){

        require_once("class/GSGroles.php");
        $this->userRoles = new GSGroles();
        return $this->userRoles->message;

    }
    protected function register_cpts(){
       // Set up the data model!
       $CStype = (new SelectHelper("cstype","Case Study Type","What is the type of the case study?"))
           ->addOption("s","Small")
           ->addOption("m","Medium")
           ->addOption("l","Large")
           ->addOption("xl","Extra Large");

       $seqdesc = " For now this is sorted descending, so anything with a value will sort before anything without.";

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
           //->addField($CStype)
           ->addField(new FieldHelper("sequence","Sequence","Used to infuence the order that posts, pages etc are shown.".$seqdesc))
           ->addField(new CPTSelectHelper("nabowner","Owning NAB","Which NAB 'owns' - is responsible for the content of - this item.",["posttype"=>"gsg_nab"]))
           ->allowComments()
           ->allowExcerpt();

       $rept = new GsgCpt("report","Report","Reports",[],__FILE__);
       $rept->urlSlug('reports')
           ->addField(new PostSelector("report_media","Choose the pdf file from the media library","Add the post number of the attachment",["posttypes"=>["attachment"],"filetypes"=>["pdf"]]))
           ->addField(new FieldHelper("post-class","Post-specific CSS classes","The class will be added to post and to links. Can be multiple, separated by spaces."))
           ->addField(new PostSelector("author_contact","Author contact or organisation","The real author, if a known contact in the system",["posttypes"=>["gsg_contact","gsg_nab"]]))
           ->addField(new FieldHelper("actual_author","Actual Case Study Author","The real author, if not a contact in the system"))
           ->addField(new MediaSelector2("author_image","Author Photo","If not a contact, upload the image to the media library first, then refer to it here."))
           ->addField(new FieldHelper("sub-heading","Subheading under the main header","Optional"))
           ->addField(new FieldHelper("title-short","Short version of the title","The default will be an abbreviation to ".$this->title_chars_short." chars"))
           ->addField(new FieldHelper("title-very-short","Very short version of the title","The default will be an abbreviation to ".$this->title_chars_very_short." chars"))
           ->addField(new GSGStatistic("statistics","Stand-out Statistics","The most important statistics, highlighted for the public"))
           ->addField(new FieldHelper("sequence","Sequence","Used to infuence the order that posts, pages etc are shown.".$seqdesc))
           ->addField(new CPTSelectHelper("nabowner","Owning NAB","Which NAB 'owns' - is responsible for the content of - this item.",["posttype"=>"gsg_nab"]))
           ->allowExcerpt()
           ->allowComments();
       $nab = new GsgCpt("nab","National Advisory Board","National Advisory Boards",[],__FILE__);
       $nab->urlSlug('nabs')
           ->addField(new SiteArea("all-nab-text","Text at the beginning of all NABs","This is the same for all NABs"))
           ->addField(new FieldHelper("post-class","Post-specific CSS classes","The class will be added to post and to links. Can be multiple, separated by spaces."))
           ->addField(new FieldHelper("nab_web_url","Website URL","The URL of the website for this NAB, if there is one."))
           ->addField(new FieldHelper("contact_email","Contact Email OR URL","Either a NAB contact email (like info@...) or URL of the contact us page."))
           ->addField(new GSGStatistic("statistics","Stand-out Statistics","The most important statistics, highlighted for the public"))
           ->addField(new MediaSelector2("flag","National Flag","The National Flag associated with this NAB"))
           ->addField(new FieldHelper("initial","Initial Letter","For the initial letter index"))
           ->addField(new FieldHelper("sequence","Sequence","Used to infuence the order that posts, pages etc are shown.".$seqdesc))
           ->addField(new CPTSelectHelper("nabowner","Associated NAB","This must be set to the NAB that this is",["posttype"=>"gsg_nab"]))
           ->addField(new CheckBox("isntnab","Check if not really a NAB","This is for GSG, which should be in the afiliation lists but should be excluded from some selections"))
           // this is so that NABs can be queries alongside contacts in the Contact Us page - it stores field is_person=0 for all NABs on Save or Update
           ->addConstant("is_person",0)
           ->addConstant("is_non_clickable",0)
           ->addConstant("is_noncontactable",0)
           ->allowExcerpt();

       $contact = (new GsgCpt("contact","Contact","Contacts",[],__FILE__))
           ->urlSlug('contacts')
           //->addField(new MediaSelector2("header-image","Header image","Upload the image to the media library - remember to give it a caption"))
           ->addField(new FieldHelper("post-class","Post-specific CSS classes","The class will be added to post and to links. Can be multiple, separated by spaces."))
           ->addField(new FieldHelper("contact_url","Contact URL","The URL of the website for this content. For a person this could be a Linkedin page. For an organisation, their home page"))
           ->addField(new FieldHelper("contact_email","Contact Email OR URL","Email for person, used in the contact form, OR for an organisation, it could be the URL of a contact page on their website."))
           ->addField(new FieldHelper("organisation","Title/Organisation","e.g. CEO, XYZ Foundation"))
           ->addField(new FieldHelper("logocolour","Logo Background colour","For those organisational contacts which have a transparent logo, use this background colour"))
           ->addField(new CheckBox("is_person","Is person","Check for person, uncheckedfor an organisation."))
           ->addField(new CheckBox("frontpage","Show on front page?","Check if this person or organisation is on the front page."))
           ->addField(new CheckBox("is_nab_board","Is a board member of their NAB?","Check if this person is on the board of their respective NAB. Shows on the NAB page"))
           ->addField(new CheckBox("is_nab_mgmt","Is in the NAB management team","Check if this person is in management for their respective NAB. Shows on the NAB page"))
           ->addField(new CheckBox("is_non_clickable","Contact is non-clickable","Only minimum info is kept for this person, do not click through to the person page."))
           ->addField(new CheckBox("is_noncontactable","Contact is not contactable","The person/organisation is associated but isnt contactable with a form."))
           ->addField(new FieldHelper("initial","Initial Letter","For the initial letter index"))
           ->addField(new FieldHelper("sequence","Sequence","Used to infuence the order that posts, pages etc are shown.".$seqdesc))
           ->addField(new CPTSelectHelper("nabowner","Associated NAB","Which NAB is this contact associated with?",["posttype"=>"gsg_nab"]))
           ->allowExcerpt();

        // extra metabox full of properties for pages
        $pageextra = (new GsgCpt("page",null,null,[],__FILE__))
           //->addField(new MediaSelector2("header-image","Header image","Upload the image to the media library - remember to give it a caption"))
           ->addField(new FieldHelper("over-heading","The header that goes above the main header","If it is the same as the start of the title, that part will be removed from the title"))
            ->addField(new FieldHelper("sub-heading","Subheading under the main header","Optional"))
           ->addField(new FieldHelper("link-heading","The heading on a link ot this page","The heading that shows on any links to this page"))
           ->addField(new FieldHelper("post-class","Post-specific CSS classes","The class will be added to post and to links. Can be multiple, separated by spaces. e.g. orange-heading, green-heading, blue-heading"))
           ->addField(new FieldHelper("post_cta","Call to Action","Imperative call for page/post summaries"))
           ->addField(new TextAreaHelper("page_excerpt","Excerpt","Excerpt for subpages."))     // normal excerpts dont apply to pages
           ->addField(new FieldHelper("sequence","Sequence","Used to infuence the order that posts, pages etc are shown.".$seqdesc));

        $postextra = (new GsgCpt("post",null,null,[],__FILE__))
            ->addField(new PostSelector("author_contact","Author contact or organisation","The real author, if a known contact in the system",["posttypes"=>["gsg_contact","gsg_nab"]]))
            ->addField(new FieldHelper("actual_author","Actual Case Study Author","The real author, if not a contact in the system"))
            ->addField(new MediaSelector2("author_image","Author Photo","If not a contact, upload the image to the media library first, then refer to it here."))
            ->addField(new FieldHelper("sequence","Sequence","Used to infuence the order that posts, pages etc are shown.".$seqdesc))
            ->addField(new FieldHelper("post-class","Post-specific CSS classes","The class will be added to post and to links. Can be multiple, separated by spaces. e.g. orange-heading, green-heading, blue-heading"))
            ->addField(new FieldHelper("over-heading","The header that goes above the main header","If it is the same as the start of the title, that part will be removed from the title"))
            ->addField(new FieldHelper("sub-heading","Subheading under the main header","Optional"))
            ->addField(new FieldHelper("title-short","Short version of the title","The default will be an abbreviation to ".$this->title_chars_short." chars"))
            ->addField(new FieldHelper("title-very-short","Very short version of the title","The default will be an abbreviation to ".$this->title_chars_very_short." chars"))
           ->addField(new CPTSelectHelper("nabowner","Associated NAB","Which NAB is this contact associated with?",["posttype"=>"gsg_nab"]));

        $eventextra = (new GsgCpt("event",null,null,[],__FILE__))
            ->addField(new FieldHelper("mobexcerpt","Short excerpt","Very short, for display on the mobile"))
           ->addField(new CPTSelectHelper("nabowner","Associated NAB","Which NAB is this event associated with? If you choose none, then it will be associated with ALL NABs",["posttype"=>"gsg_nab"]));

        //$contact2 = GsgCpt::get("contact");
        //error_log("---- got cpt ".($contact2 ? "yes" : "no"));
     }

    /**
     * Add data used by fastpage templates
     * @param $postdata
     */
    public function add_post_mosaic_data($postdata){
         $id = $postdata["id"];

         $title = $postdata["title"];
         $sh = get_post_meta($id, "title-short", true);
         $postdata["title-short"] = $sh ?: $this->shorten_to_word($title, $this->title_chars_short);

         $vsh = get_post_meta($id, "title-very-short", true);
         $postdata["title-very-short"] = $vsh ?: $this->shorten_to_word($title, $this->title_chars_very_short);

         $authordata = $this->get_author($id);
         $postdata["author-data"] = $authordata;
         $postdata["author"] = $authordata["name"];

         return $postdata;
     }
     // obsolete?
     public function flush(){
         //traceit("GSG flushing rule on activation/deactivation");
         flush_rewrite_rules();
     }
     /**
     * Correct an incorrect rewrite rule.
     * I havent found which plugin is adding this rule, but it is clearly wrong, since the action is for a year
     * but the rule doesnt match on 2 or 4 digits.
     * If you remove this, then the symptom is that all accesses to a page dont show that page, they show a list of recent posts.
     */
     public function correctRules($rules){
         if (isset($rules['([^/]+)/?$']) && $rules['([^/]+)/?$']=='index.php?year=$matches[1]') {
             unset($rules['([^/]+)/?$']);
             //if (TRACEIT) traceit("Found the offending rule. Re-write rules all ".print_r($rules,true));
         }

         return $rules;
     }
     // Only two things are guaranteed in this world, death and taxonomies.
     public function register_taxes(){

       $posttypes = ['post','page','gsg_casestudy','gsg_report'];
       register_taxonomy(
         'region',
         $posttypes,
         array(
			        'labels'       => ["singular_name" => __( 'Region' ),
                           "name" => __('Regions'),
                          ],
			        'rewrite'      => array( 'slug' => 'region' ),
              'hierarchical' => true,
              'description'  => 'Region',
              )
	     );
       register_taxonomy(
         'sector',
         array_merge($posttypes,["gsg_contact"]),
         array(
			        'labels'       => ["singular_name" => __( 'Sector' ),
                           "name" => __('Sectors'),
                          ],
			        'rewrite'      => array( 'slug' => 'sector' ),
              'hierarchical' => true,
              'description'  => 'Investment Sector',
              )
	     );
       register_taxonomy(
         'year',
         $posttypes,
         array(
			        'labels'       => ["singular_name" => __( 'Year' ),
                           "name" => __('Years'),
                          ],
			        'rewrite'      => array( 'slug' => 'year' ),
              'hierarchical' => true,
              'description'  => 'Year of initiation',
              )
	     );
       // GSG-206 this one doesnt have a good name, it refers to the role in the market - supply,demand.facilitator
         register_taxonomy(
             'supdem',
             ['post','page','gsg_casestudy','gsg_report','gsg_contact'],
             array(
                 'labels'       => ["singular_name" => __( 'Supply/Demand' ),
                     "name" => __('Supply/Demand'),
                 ],
                 'rewrite'      => array( 'slug' => 'mktrole' ),
                 'hierarchical' => true,
                 'description'  => 'Role in the Market',
             )
         );
         // GSG-214 Different types of roles within the NAB
         register_taxonomy(
             'nabrole',
             ['gsg_contact'],
             array(
                 'labels'       => ["singular_name" => __( 'Role in NAB' ),
                     "name" => __('Role in NAB'),
                 ],
                 'rewrite'      => array( 'slug' => 'nabrole' ),
                 'hierarchical' => true,
                 'description'  => 'Role within the National Advisory Board',
             )
         );
       /*
        * Expects these slugs in the tax: featured-news, no-paragraph, pillar
        */
       register_taxonomy(
         'editorial',
           ['post','page','gsg_casestudy','gsg_report','gsg_nab'],
         array(
			        'labels'       => ["singular_name" => __( 'Editorial Category' ),
                           "name" => __('Editorial Categories'),
                          ],
              'public'       => true,
			        'rewrite'      => array( 'slug' => 'editorial' ),
              'hierarchical' => true,
              'description'  => 'Categories used for extra controls, searching or presentation',
              'capabilities' => ['manage_terms'  => 'manage_options',
                    'edit_terms'    => 'manage_options',
                    'delete_terms'  => 'manage_options',
                    'assign_terms'  => 'manage_options',
                ],   // admin only
              )
	     );

     }
     // These are defined for all pages because they need to be available in the header for some designs - so that the filter
     // can be created in the header. Doing them purely as shortcodes wouldnt allow that. However by defining them all up front
     // the same filterloop can be partly in the template and partly as a shortcode.
     public function setupFilters(){
       global $filterloop;
       if (!$filterloop) return;

       $buttons = ["filter_button" => "Search",
            "clear_button"  => "Clear",
            "before_loop"   => "",
            "after_loop"    => "",
            // paginate
            "before_page"   => '<div class="pagination">
                    <div class="numeric-pagination">
                      <ul>',
            "after_page"    => '</ul>
                               </div>
                              </div>',
            "tmpl-link"     => '<li><a href="%url%">%lab%</a></li>',
            "tmpl-current"  => '<li class="active"><a href="#">%lab%</a></li>',
            //   "pageprev"      => "<div class=\"arrow prev\"></div>",
            //   "pagenext"      => "<div class=\"arrow next\"></div>",

        ];
       $news = $filterloop->make("news",["loopslug"=>'template-parts/post/content',"loopname"=>'link', "before_loop"=>'<ul class="case-studies-grid">', "after_loop"=>'</ul>'], $buttons)
          ->setPostType(["post"])
          ->setPageSize(6)
           ->resetSequence();

       $featurednews = $filterloop->make("featurednews",["loopslug"=>'template-parts/post/content',"loopname"=>'linkfeat'],$buttons)
          ->setPostType(["post"])
          ->setPageSize(1)
          ->getQuery()->addTaxPredicate(['editorial','slug',['featured-news']])
                        ->resetSequence();

       $forthcoming = $filterloop->make("forthcoming",["loopslug"=>'template-parts/post/content',"loopname"=>'post'], $buttons)
          ->setPostType("event")
          ->setPageSize(2);

       $mainpeople = $filterloop->make("mainpeople",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link'], $buttons)
           ->describe("A list of all people contacts, which are checked for appearing on the front page.")
          ->setPostType("gsg_contact")
          ->unPaged()
          ->getQuery()->addFieldPredicate(["frontpage","=","1" ])
                      ->addFieldPredicate(["is_person","=","1" ]);

         $mainpeoplediv = $filterloop->make("mainpeoplediv",["loopslug"=>'template-parts/%type%/content',"loopname"=>'linkdiv'], $buttons)
             ->describe("A list of all people contacts, which are checked for appearing on the front page, using div instead of list, for the scroller")
             ->setPostType("gsg_contact")
             ->unPaged()
             ->getQuery()->addFieldPredicate(["frontpage","=","1" ])
             ->addFieldPredicate(["is_person","=","1" ]);

         $allpeople = $filterloop->make("allpeople",["loopslug"=>'template-parts/%type%/content',"loopname"=>'linksmall', "before_loop"=>'<ul class="person-list">', "after_loop"=>'</ul>'],$buttons)
           ->describe("A list of all contacts, people and organisations, 12 per page, using the small format business card tile, filtered by afiliation, sector, and initial letter.")
          ->setPostType(["gsg_contact","gsg_nab"])
          ->setPageSize(12)
          ->addFilter(new SearchFilter("textsearch"))
          ->addFilter((new FieldFilter("is_person",new Radio()))
                      ->setMap(["0"=>"Organisations", "1"=>"People"]))
          ->addFilter((new TaxFilter("sector",new FancySelect()))
                      ->setTitle("Issue"))
          ->addFilter((new PostidFilter("nabowner",new FancySelect()))
                      ->setTitle("Affiliation"))
           ->addFilter((new TaxFilter("supdem",new FancySelect()))
                        ->setTitle("Market Sector"))
          ->addFilter((new FieldFilter("initial",new CompactRadio())))
             ->getQuery()->resetSequence()
                        ->orderbyMeta("sequence");

       $tworeports =  $filterloop->make("tworeports",["loopslug"=>'template-parts/%type%/content',"loopname"=>'biglink'],$buttons)
           ->describe("Just two reports, not paginated or filtered. They show quite big. For the resourcs page.")
           ->setPostType(["gsg_report"])
           ->setPageSize(2);

       $knowledge = $filterloop->make("knowledge",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link', "before_loop"=>'<ul class="case-studies-grid">', "after_loop"=>'</ul>'],$buttons)
           ->describe("Case studies and reports, in a variable width pattern, using the template which shows the author's picture. It has the main filters, region, sector. Originally on Resources page")
           ->setPostType(["gsg_casestudy","gsg_report"])
           ->setPageSize(6)
           ->addFilter(new SearchFilter("textsearch"))
           ->addFilter((new TaxFilter("region",new FancySelect()))
                      ->setTitle("Region"))
           ->addFilter((new TaxFilter("sector",new FancySelect()))
                      ->setTitle("Issue"))
           ->addFilter((new TaxFilter("supdem",new FancySelect()))
                        ->setTitle("Market Sector"));

         $segknowledge = $filterloop->make("segknowledge",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link', "before_loop"=>'<ul class="case-studies-grid">', "after_loop"=>'</ul>'], $buttons)
             ->describe("Just news posts, case studies and reports, in a variable width pattern, for a given supply/demand focus")
             ->setPageSize(9)
             ->setPostType(["gsg_casestudy","gsg_report","post"])
             ->getQuery()->addTaxPredicate(['supdem','slug',"=>supdemid"]) ;  // pick up the supply/demand id
                       // ->resetSequence();

         $wanttolearn = $filterloop->make("iwanttolearn",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link', "before_loop"=>'<ul class="case-studies-grid">', "after_loop"=>'</ul>'], $buttons)
             ->describe("News posts, case studies and reports, tagged with 'Home i want to learn' in the editorial classification.")
             ->setPageSize(9)
             ->setPostType(["gsg_casestudy","gsg_report","post"])
             ->getQuery()->addTaxPredicate(['editorial','slug',"home-i-want-to-learn"]) ;  // only those with the tag

         $wanttolearnseg = $filterloop->make("iwanttolearnseg",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link', "before_loop"=>'<ul class="case-studies-grid">', "after_loop"=>'</ul>'], $buttons)
             ->describe("News posts, case studies and reports, tagged with 'i want to learn' in the editorial classification, and specific to a particular supply/demand segment.")
             ->setPageSize(9)
             ->setPostType(["gsg_casestudy","gsg_report","post"])
             ->getQuery()->addTaxPredicate(['editorial','slug',"i-want-to-learn"])   // only those with the tag
             ->addTaxPredicate(['supdem','slug',"=>supdemid"]);              // pick up the supply/demand id

         $segcontacts = $filterloop->make("segcontacts",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link',"before_loop"=>'<ul>', "after_loop"=>'</ul>'],$buttons )
             ->describe("Contactable contacts with a given supply/demand focus")
             ->setPostType(["gsg_contact"])
             ->unPaged()
             ->getQuery()->addFieldPredicate(["is_person","=","1" ])        // exclude organisations
                        ->addFieldPredicate(["is_nab_board","=","1" ])
                        ->addTaxPredicate(['supdem','slug',"=>supdemid"]);   // pick up the supply/demand id

       $nabknowledge = $filterloop->make("nabknowledge",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link', "before_loop"=>'<ul class="case-studies-grid">', "after_loop"=>'</ul>'], $buttons)
           ->describe("Just news posts, case studies and reports, in a variable width pattern, using the template with no author picture. Implicitly filtered by NAB, which it takes from the NAB page it is on.")
           ->setPageSize(9)
           ->setPostType(["gsg_casestudy","gsg_report","post"])
           ->getQuery()->addFieldPredicate(['nabowner','=',"=>nabid"])   // pick up the nab id
                        ->resetSequence();

         $nabpeople = $filterloop->make("nabpeople",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link',"before_loop"=>'<ul>', "after_loop"=>'</ul>'],$buttons )
             ->describe("Contacts for the specific NAB which have have a given NAB relationship category. In the shortcode add role= and the slug of the category")
             ->setPostType(["gsg_contact"])
             ->unPaged()
             ->getQuery()->addFieldPredicate(["is_person","=","1" ])        // exclude organisations
                        ->addFieldPredicate(['nabowner','=',"=>nabid"])   // pick up the nab id
                        ->addTaxPredicate(['nabrole','slug',"=>role"])    // pick up the role in NAB category
                        ->resetSequence()
                        ->orderbyMeta("sequence");

         $nabpeoplediv = $filterloop->make("nabpeoplediv",["loopslug"=>'template-parts/%type%/content',"loopname"=>'linkdiv'],$buttons )
             ->describe("Contacts for the specific NAB which have have a given NAB relationship category. In the shortcode add role= and the slug of the category. Version with divs to use with slider")
             ->setPostType(["gsg_contact"])
             ->unPaged()
             ->getQuery()->addFieldPredicate(["is_person","=","1" ])        // exclude organisations
                        ->addFieldPredicate(['nabowner','=',"=>nabid"])   // pick up the nab id
                        ->addTaxPredicate(['nabrole','slug',"=>role"])     // pick up the role in NAB category
                        ->resetSequence()
                        ->orderbyMeta("sequence");

         $nabboard = $filterloop->make("nabboard",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link',"before_loop"=>'<ul>', "after_loop"=>'</ul>'],$buttons )
           ->describe("Contacts for the specific NAB which have the Is Board Member checked")
           ->setPostType(["gsg_contact"])
           ->unPaged()
           ->getQuery()->addFieldPredicate(["is_person","=","1" ])        // exclude organisations
                       ->addFieldPredicate(["is_nab_board","=","1" ])
                       ->addFieldPredicate(['nabowner','=',"=>nabid"])   // pick up the nab id
                        ->resetSequence()
                        ->orderbyMeta("sequence");

         $nabboarddivs = $filterloop->make("nabboarddiv",["loopslug"=>'template-parts/%type%/content',"loopname"=>'linkdiv'],$buttons )
             ->describe("Contacts for the specific NAB which have the Is Board Member checked - in div form, not as list items")
             ->setPostType(["gsg_contact"])
             ->unPaged()
             ->getQuery()->addFieldPredicate(["is_person","=","1" ])        // exclude organisations
                        ->addFieldPredicate(["is_nab_board","=","1" ])
                        ->addFieldPredicate(['nabowner','=',"=>nabid"])   // pick up the nab id
                        ->resetSequence()
                        ->orderbyMeta("sequence");

        $orgreveal = "<div class='content centre-wrapped'><div class='btn %class%' %other%>%msg%</div></div>";

       $naborgs = $filterloop->make("naborgs",["loopslug"=>'template-parts/%type%/content',"loopname"=>'linksmall',"before_loop"=>'<ul class="person-list">', "after_loop"=>'</ul>', 'reveal_button'=>$orgreveal, 'reveal_msg'=>'Show all', 'reveal_hide'=>'Hide'],$buttons )
           ->describe("Organisations supporting the given NAB")
           ->setPostType(["gsg_contact"])
           ->unPaged()
           ->reveal(12)
           ->getQuery()->addFieldPredicate(["is_person","=","0" ])        // exclude people
                       ->addFieldPredicate(['nabowner','=',"=>nabid"])   // pick up the nab id
                        ->resetSequence()
                        ->orderbyMeta("sequence");


       $nabmgmt = $filterloop->make("nabmgmt",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link',"before_loop"=>'<ul>', "after_loop"=>'</ul>'],$buttons )
           ->describe("Contacts for the specific NAB which have the Is Management checked")
           ->setPostType(["gsg_contact"])
           ->setPageSize(12)
           ->getQuery()->addFieldPredicate(["is_person","=","1" ])        // exclude organisations
                       ->addFieldPredicate(["is_nab_mgmt","=","1" ])
                       ->addFieldPredicate(['nabowner','=',"=>nabid"])   // pick up the nab id
                        ->resetSequence()
                        ->orderbyMeta("sequence");

         $nabmgmtdiv = $filterloop->make("nabmgmtdiv",["loopslug"=>'template-parts/%type%/content',"loopname"=>'linkdiv'],$buttons )
             ->describe("Contacts for the specific NAB which have the Is Management checked")
             ->setPostType(["gsg_contact"])
             ->unPaged()
             ->getQuery()->addFieldPredicate(["is_person","=","1" ])        // exclude organisations
                        ->addFieldPredicate(["is_nab_mgmt","=","1" ])
                        ->addFieldPredicate(['nabowner','=',"=>nabid"])   // pick up the nab id
                        ->resetSequence()
                        ->orderbyMeta("sequence");


       $nabevents = $filterloop->make("nabevents",["loopslug"=>'template-parts/%type%/content',"loopname"=>'link',"before_loop"=>'<ul class="events-list">', "after_loop"=>'</ul>'], $buttons )
           ->setPostType(["event"])
           ->setPageSize(3)
           ->getQuery()->addFieldPredicate(['nabowner','=',"=>nabid"]);   // pick up the nab id
         
       $pillars = $filterloop->make("pillars",["loopslug"=>'template-parts/page/content',"loopname"=>'subpage'], $buttons)
               ->setPostType('page')
               ->unPaged()
               ->getQuery()->addTaxPredicate(['editorial','slug',['pillar']])
                            ->ascending(true)
                            ->orderbyMeta("sequence");

         if ($this->useFastPager){

             // all the tiles for these are knowledge tiles - case studies, reports, sometimes posts
             $filterloop->get("knowledge")          ->unPaged()->fastPage()->addView("list","ListView")->addView("normal","ISFPView");
             $filterloop->get("news")               ->unPaged()->fastPage(["mosaicsize"=>3])->addView("list","ListView")->addView("normal","ISFPView");
             $filterloop->get("nabknowledge")       ->unPaged()->fastPage(["mosaicsize"=>9])->addView("list","ListView")->addView("normal","ISFPView");
             $filterloop->get("segknowledge")       ->unPaged()->fastPage(["mosaicsize"=>9])->addView("list","ListView")->addView("normal","ISFPView");
             $filterloop->get("iwanttolearn")       ->unPaged()->fastPage(["mosaicsize"=>9])->addView("list","ListView")->addView("normal","ISFPView");
             $filterloop->get("iwanttolearnseg")    ->unPaged()->fastPage(["mosaicsize"=>9])->addView("list","ListView")->addView("normal","ISFPView");

         }
     }

     public function is_subpage(){
         global $post;
         return ($post && $post->post_type == "page" && has_term("pillar", "editorial"));
     }

    /**
     * Calculate an appropriate link - taking the 4 pillar pages in a cycle and substituting %url% with the previous or next depending on $right
     * @param $template string HTML template
     * @param $right boolean true if the right link is wanted, otherwise the left
     * @return HTML string
     */
     public function subpage_link($template, $right){
         global $post;
         $pillars = $this->get_pillars();
         $ix = array_search($post->ID, $pillars);
         $num = count($pillars);
         if ($right){
             $linkix = $ix+1;
             if ($linkix>=$num) $linkix=0;
         } else {
             $linkix = $ix-1;
             if ($linkix<0) $linkix=$num-1;
         }
         $url = get_permalink($pillars[$linkix]);
         return str_replace("%url%",$url,$template);
     }
     protected function get_pillars(){
         if ($this->pillarlist) return $this->pillarlist;
         global $wpdb;

         $s = 'select TR.object_id as postid
              from '.$wpdb->term_taxonomy.' TT, '.$wpdb->terms.' T, '.$wpdb->term_relationships.' TR, '.$wpdb->postmeta.' PM 
              where TT.taxonomy = "editorial"
              and TT.term_id = T.term_id
              and T.slug = "pillar"
              and TT.term_taxonomy_id = TR.term_taxonomy_id
              and PM.meta_key = "sequence"
              and PM.post_id = TR.object_id
              order by PM.meta_value';

         $this->pillarlist = $wpdb->get_col($s);

         if (TRACEIT) traceit("pillars SQL: ".$s);
         return $this->pillarlist;
     }

    /**
     * Actions that need to be done in the Wordpress header.
     * At this point the query object is set up and $post exists.
     * Use for setting filter loop scope variables for NAB pages and sub-pages
     * Disable wpautop for pages baed on the editorial category
     */
    public function wp_head(){
         global $post;
         global $filterloop;

         // if it is a page with editorial pillar set, then get the supdem value and set that
         if ($this->is_subpage()){
             $segmentterms = wp_get_post_terms($post->ID, "supdem", ["fields"=>"all"]);
             if ($segmentterms) {
                 $filterloop->setValue("supdemid",$segmentterms[0]->slug);
             }
         }
         if (is_single()){
             if ($post){
                 // if it is a nab page set the nab value
                 if ($post->post_type == "gsg_nab"){
                     $filterloop->setValue("nabid",$post->ID);		// referenced in these queries
                 }
             }

         }

        if (has_term("no-paragraph", "editorial")){
            if (TRACEIT) traceit("Turning off wpautop for thispage");
            remove_filter( 'the_content', 'wpautop' );
        }
     }

     /**
     * Function to be executed in the header part of the body (not the head tag). Anything that needs to be done on every page.
     */
     // obs
     public function head(){
         if (TRACEIT) traceit("Calling the GSG head function");

         $personformid = get_site_option($this->contactopt);
         if ($personformid) $this->the_contact_popup_form($personformid,$this->contactformid);
     }
     /**
     * A switch for the sidebars - but it has CSS implications too.
     */
     public function showSides(){
        return false;
     }
     /**
     * return contact email/URL for person/organisation, if they are contactable.
     * @param integer Post id, assumed to be that of a contact post type
     * @return array("url"=> url or null, "email"=> email address or null, "contactable"=>boolean "name"=>string)
     * An array will always be returned, it will always have contactable, url and email properties. They may be null.
     */
     public function getContactInfo($postid){
         $name = get_the_title($postid);

         if (get_post_meta($postid, "is_noncontactable", true)==1) return ["url"=>null,"email"=>null,"contactable"=>false, "name"=>$name];

         $contact = get_post_meta($postid, "contact_email",true);
         if (!$contact) return ["url"=>null,"email"=>null,"contactable"=>false, "name"=>$name];
         if (strpos($contact,"@")!==false) return ["url"=>null,"email"=>$contact,"contactable"=>true, "name"=>$name];

         return ["url"=>$contact,"email"=>null,"contactable"=>true, "name"=>$name];
     }

     /**
      * Get the author info for a content post - case study, report or news post.
      * @param $postid
      * @return array ["imagesrc"=>url/null, "name"=>text, "url"=>url of the contact if in the system or null]
      *
     */
     public function get_author($postid){
         $contactid = get_post_meta($postid, "author_contact",true);
         if ($contactid){
             $name = get_the_title($contactid);
             $im = get_the_post_thumbnail_url($contactid, 'gsg-square-thumbnail');
             $url = get_permalink($contactid);
         } else {
             $name = get_post_meta($postid, "actual_author",true);
             $imid = get_post_meta($postid, "author_image",true);
             $im =  wp_get_attachment_url($imid);
             $url = null;
         }
         return ["imagesrc"=>$im, "name"=>$name, "url"=>$url];
     }

    /**
     * Return the download URL for a report.
     * @return false|null| URLstring
     */
    public function get_download(){
         global $post;
         $report = get_post_meta($post->ID, "report_media", true);
         return $report ? wp_get_attachment_url($report) : null;
     }

    /**
     * Get the case study logo - supply a template and if there is no logo, then nothing is returned.
     * @param $template string - should contain %url% which will be substituted with the lgo URL.
     * @param null $postid
     * @return mixed| HTMLstring the substituted template
     */
    public function get_casestudy_logo($template, $postid = null){
         global $post;
         $pid = $postid ?: $post->ID;
         $logid = get_post_meta($pid, "casestudy_logo", true);
         if (!$logid) return "";
         $logo = wp_get_attachment_url($logid);
         return $logo ? str_replace("%url%", $logo, $template) : "";
     }

    /**
     * Return video info for template.
     * @param null $postid - optionsl postid - uses the current post by default.
     * @return HTML string for displaying the video, if it has been defined, otherwise an empty string.
     */
    public function get_video($postid = null){
         global $post;
         $pid = $postid ?: $post->ID;
         $vidurl = get_post_meta($pid, "video_url", true);
         return $vidurl ? wp_oembed_get($vidurl) : "";
     }
    /**
     * Return the flag img tag for a given NAB
     * @param null $postid - optionsl postid - uses the current post by default.
     * @return HTML string for displaying the video, if it has been defined, otherwise an empty string.
     */
    public function get_flag($postid = null){
        global $post;
        $pid = $postid ?: $post->ID;
        $flagid = get_post_meta($pid, "flag", true);
        return $flagid ? '<img class="nab-flag" src="'.esc_url(wp_get_attachment_url($flagid)).'">' : "";
    }
    /**
     * Get the sub heading
     * @param $template string - should contain %s% which will be substituted with the actual title.
     * @param null $postid
     * @return mixed| HTMLstring the substituted template
     */
    public function get_subheading($template, $postid = null){
        global $post;
        $pid = $postid ?: $post->ID;
        $sh = get_post_meta($pid, "sub-heading", true);
        return $sh ? str_replace("%s%", $sh, $template) : "";
    }
    // ************************************************************************************************************************************
    // CONTACT INFO AND FORM STUFF ********************************************************************************************************
    // ************************************************************************************************************************************
     /**
     * Return a contact form from the title
     * This assumes that Contact Form 7 is being used at this point.
     * @param $formtitle string or numeric If string, it is the Title of the form, if numeric, is the id.
     * @return html String - the form, or an empty string if there isnt one with that title.
     */
     public function contactForm($formtitle){
         if (is_numeric($formtitle)) $formid = $formtitle;
         else {
             $formid = $this->get_form_by_name($formtitle);
             if (!$formid) return "";
         }
         $shortcode = '[contact-form-7 id="'.$formid.'" title="'.$formtitle.'"]';
         return do_shortcode($shortcode);
     }
     protected function get_form_by_name($formtitle){
         global $wpdb;
         $s = "select ID, post_title from $wpdb->posts where post_title=%s and post_status='publish' and post_type = 'wpcf7_contact_form';";
         $res = $wpdb->get_results($wpdb->prepare($s,$formtitle),ARRAY_N);
         if (count($res)==0) return null;
         return $res[0][0];
     }

    /**
     * This is a general contact form to be shown on every page in order to provide a popup. It is initially hidden.
     * It assumes that the Easy Fancybox plugin is present.
     * @param $formtitle string or numeric - if numeric is is the post id of the form
     */
    public function the_contact_popup_form($formtitle,$formid = null){
         echo '<div class="fancybox-hidden" style="display: none;">';
         echo '<div id="'.($formid ?: $this->contactformid).'"><div class="title">Contact <span class="contact_form_name"></span></div>'.$this->contactForm($formtitle).'</div>';
         echo '</div>';
     }

    /**
     * @param $postid numeric of the gsg_contact post which is to be contacted by this button
     * @param string $classes
     */
    public function the_contact_popup_button($postid, $classes="", $formid = null){
         $contact = $this->getContactInfo($postid);

         if (!$contact["contactable"]) return;
         if ($contact["url"]) echo '<a href="'.$contact["url"].'" class="contact-link"><div class="contact-button">Get in touch</div></a>';
         if ($contact["email"]) echo '<a href="#'.($formid ?: $this->contactformid).'" class="contact-popup '.$classes.'"><div class="contact-button" data-contactid="'.$postid.'" data-contactname="'.esc_attr($contact["name"]).'">Get in touch</div></a>';
         return;
     }

    /**
     * This is intended to be a shortcode handler for creating a contact form popup. GSG-280.
     * The client has now decided not to include this, but if it comes back, this can be built quickly using the components above.
     * Add a hidden form inline for each invocation of the shortcode, with a generated id to hold them together.
     * @param $atts
     * @param $content
     */
    public function do_contact_popup_shc($atts, $content){

     }
    // ************************************************************************************************************************************
    // NAB (country page) things ********************************************************************************************************
    // ************************************************************************************************************************************
     /**
     * Create a list of NAB flags based on the settings in the db
     * It belongs here because the flags custom field is defined here.
     * @param $template is a piece of html for a single flag which should contain the image tag and it's link.
     * It should contain %src% which is the image URL. It should also contain %url% which is the URL of the associated NAB page.
     * TODO special formatting if the current page is one of these pages?
     */
     public function nabFlags($template=null){

        $templ = $template ?: "<div class='nab-flag'><a href='%url%'><img src='%src%'></a></div>";

        $m = '';
        $res = $this->getFlags();

        $places = ["%src%","%url%","%title%"];

        foreach($res as $nabflag){
            $m.=str_replace($places,[wp_get_attachment_url($nabflag["flag"]), get_permalink($nabflag["nab"]), $nabflag["title"]],$templ);
        }
        return $m;
     }

     /**
     * How many NABs are there, excluding GSG. It is sufficient to count the flags
     */
     public function nabNumber(){
        return count($this->getFlags());
     }

     /**
     * Internal function to get the flags and return them, also to store them in the class so they are only got once.
     */
     protected function getFlags(){
         global $wpdb;
         if ($this->flags) return $this->flags;

         // TODO this query is missing some checks which could be useful.
         $s = "select N.post_id as nab, N.meta_value as flag, 
              from $wpdb->postmeta as N, $wpdb->postmeta as S
              where N.post_id = S.post_id
              and S.meta_key = 'sequence'
              and N.meta_key = 'flag'
              and N.meta_value!=''
              order by S.meta_value";
         $s2 = "select N.post_id as nab, N.meta_value as flag, P.post_title as title
              from $wpdb->postmeta as N, $wpdb->posts as P
              where N.post_id = P.ID
              and P.ID = N.post_id
              and N.meta_key = 'flag'
              and N.meta_value!=''
              order by P.post_title";
         $res = $wpdb->get_results($s2,ARRAY_A);
         $this->flags = $res;
         return $res;
     }
     public function get_post_terms($tax, $firstonly = false){
         global $post;
         $terms = wp_get_post_terms($post->ID, $tax);
         $res = [];
         foreach($terms as $term){
             if ($firstonly) return $term->name;
             $res[] = $term->name;
         }
         if ($firstonly) return "";
         return $res;
     }

    /**
     * Return the templated categories - these arent normal WP categories. The NAB owner is a custom field.
     * There are categories in custom taxonomies - region, market sector etc. The links for these should be the resources
     * page with an appropriate selector already set.
     * @param $postid
     * @param string $template - should have %url% and %txt% - which will sometimes be e.g. an img.
     */
     public function get_categories($postid, $linkpagename, $template='<a href="%url%">%txt%</a>', $before='', $after=''){
         if (!$this->showcategories) return "";
         global $filterloop;

         $nabid = get_post_meta($postid,"nabowner", true);
         $m = "";
         $places = ["%url%","%txt%"];
         if ($nabid){
             $url = get_permalink($nabid);
             $flagid = get_post_meta($nabid,"flag", true);
             $imgurl = wp_get_attachment_url($flagid);
             $img = '<img src="'.$imgurl.'">';
             $m.= str_replace($places, [$url,$img],$template);
         }
         // the other custom taxonomies
         $knowledge = $filterloop->get("knowledge");

         $linkpageurl = $this->get_permalink_by_name($linkpagename);

         $m.= $this->get_cat_tax_link($postid, 'region', $template, $places, $knowledge, $linkpageurl);
         $m.= $this->get_cat_tax_link($postid, 'sector', $template, $places, $knowledge, $linkpageurl);
         $m.= $this->get_cat_tax_link($postid, 'supdem', $template, $places, $knowledge, $linkpageurl);

         return $m ? $before.$m.$after : "";
     }
     protected function get_cat_tax_link($postid, $taxid, $template, $places, $knowledge, $linkpageurl){
         $m = "";

         $terms = get_the_terms($postid, $taxid);
         if (!$terms) return "";

         $filter = $knowledge->getFilter($taxid);

         $title = $filter->getTitle();
         $title = $title ? $title.": " : "";

         foreach ($terms as $term){
             traceit("============= term found ".print_r($term,true));
             $name = $term->name;
             $id = $term->term_id;
             $url = $knowledge->get_url_for($taxid,$id, $linkpageurl);
             $m.= str_replace($places, [$url, $title.$name], $template);
         }
         return $m;
     }
     protected function get_permalink_by_name($name){
         global $wpdb;
         $post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s", $name ));
         return $post ? get_permalink($post) : "";
     }
     public function get_variable_title($position_on_page, $postid = null){
         global $post;
         $pid = $postid ?: $post->ID;

         $m = "";
         $title = get_the_title($pid);
         $sh = get_post_meta($pid, "title-short", true);
         $vsh = get_post_meta($pid, "title-very-short", true);

         $sh = $sh ?: $this->shorten_to_word($title, $this->title_chars_short);
         $vsh = $vsh ?: $this->shorten_to_word($title, $this->title_chars_very_short);

         return '<span class="variable-image-hide-s-'.$position_on_page.'">'.$sh.'</span>'.'<span class="variable-image-hide-b-'.$position_on_page.'">'.$vsh.'</span>';
     }
     protected function shorten_to_word($str,$len){
         if (strlen($str)<=$len) return $str;
         $pos = strrpos(substr($str,0,$len)," ");
         if ($pos===false) return substr($str,0,$len)."...";  // if no word boundaries then just chop it
         return substr($str,0, $pos)."...";
     }

     /**
     * Whether to show the categories next/prev, comments etc on case studies, posts, not including twentyseventeen_entry_footer
     * @return bool
     */
     public function showFootMeta(){
         return false;
     }
     public function get_carousel(){
         $val = get_site_option($this->homeCarousel->option);
         return $val>-1 ? $this->homeCarousel->get($val)  : "";
     }
}
$gsg = new GSG();
?>
