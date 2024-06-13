<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Documentation;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use function Deployer\Support\str_contains as str_contains;

class DocGen
{
    /**
     * @var string
     */
    public $root;
    /**
     * @var DocRecipe[]
     */
    public $recipes = [];

    public function __construct(string $root)
    {
        $this->root = str_replace(DIRECTORY_SEPARATOR, '/', realpath($root));
    }

    public function parse(string $source): void
    {
        $directory = new RecursiveDirectoryIterator($source);
        $iterator = new RegexIterator(new RecursiveIteratorIterator($directory), '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($iterator as [$path]) {
            $realPath = str_replace(DIRECTORY_SEPARATOR, '/', realpath($path));
            $recipePath = str_replace($this->root . '/', '', $realPath);
            $recipeName = preg_replace('/\.php$/i', '', basename($recipePath));
            $recipe = new DocRecipe($recipeName, $recipePath);
            $recipe->parse(file_get_contents($path));
            $this->recipes[$recipePath] = $recipe;
        }
    }

    public function gen(string $destination): ?string
    {
        foreach ($this->recipes as $recipe) {
            // $find will try to return DocConfig for a given config $name.
            $findConfig = function (string $name) use ($recipe): ?DocConfig {
                if (array_key_exists($name, $recipe->config)) {
                    return $recipe->config[$name];
                }
                foreach ($recipe->require as $r) {
                    if (array_key_exists($r, $this->recipes)) {
                        if (array_key_exists($name, $this->recipes[$r]->config)) {
                            return $this->recipes[$r]->config[$name];
                        }
                    }
                }
                foreach ($this->recipes as $r) {
                    if (array_key_exists($name, $r->config)) {
                        return $r->config[$name];
                    }
                }
                return null;
            };
            $findConfigOverride = function (DocRecipe $recipe, string $name) use (&$findConfigOverride): ?DocConfig {
                foreach ($recipe->require as $r) {
                    if (array_key_exists($r, $this->recipes)) {
                        if (array_key_exists($name, $this->recipes[$r]->config)) {
                            return $this->recipes[$r]->config[$name];
                        }
                    }
                }
                foreach ($recipe->require as $r) {
                    if (array_key_exists($r, $this->recipes)) {
                        return $findConfigOverride($this->recipes[$r], $name);
                    }
                }
                return null;
            };
            // Replace all {{name}} with link to correct config declaration.
            $replaceLinks = function (string $comment) use ($findConfig): string {
                $output = '';
                $code = false;
                foreach (explode("\n", $comment) as $i => $line) {
                    if (str_starts_with($line, '```') || str_starts_with($line, '~~~')) {
                        $code = !$code;
                    }
                    if ($code) {
                        $output .= $line;
                        $output .= "\n";
                        continue;
                    }
                    $output .= preg_replace_callback('#(\{\{(?<name>[\w_:\-/]+)\}\})#', function ($m) use ($findConfig) {
                        $name = $m['name'];
                        $config = $findConfig($name);
                        if ($config !== null) {
                            $md = php_to_md($config->recipePath);
                            $anchor = anchor($name);
                            return "[$name](/docs/$md#$anchor)";
                        }
                        return "{{" . $name . "}}";
                    }, $line);
                    $output .= "\n";
                }
                return $output;
            };
            $findTask = function (string $name, bool $searchOtherRecipes = true) use ($recipe): ?DocTask {
                if (array_key_exists($name, $recipe->tasks)) {
                    return $recipe->tasks[$name];
                }
                foreach ($recipe->require as $r) {
                    if (array_key_exists($r, $this->recipes)) {
                        if (array_key_exists($name, $this->recipes[$r]->tasks)) {
                            return $this->recipes[$r]->tasks[$name];
                        }
                    }
                }
                if ($searchOtherRecipes) {
                    foreach ($this->recipes as $r) {
                        if (array_key_exists($name, $r->tasks)) {
                            return $r->tasks[$name];
                        }
                    }
                }
                return null;
            };

            $title = join(' ', array_map('ucfirst', explode('_', $recipe->recipeName))) . ' Recipe';
            $config = '';
            $tasks = '';
            $intro = <<<MD
```php
require '$recipe->recipePath';
```

[Source](/$recipe->recipePath)


MD;
            if (is_framework_recipe($recipe)) {
                $brandName = framework_brand_name($recipe->recipeName);
                $typeOfProject = preg_match('/^symfony/i', $recipe->recipeName) ? 'Application' : 'Project';
                $title = "How to Deploy a $brandName $typeOfProject";

                $intro .= <<<MARKDOWN
Deployer is a free and open source deployment tool written in PHP. 
It helps you to deploy your $brandName application to a server. 
It is very easy to use and has a lot of features. 

Three main features of Deployer are:
- **Provisioning** - provision your server for you.
- **Zero downtime deployment** - deploy your application without a downtime.
- **Rollbacks** - rollback your application to a previous version, if something goes wrong.

Additionally, Deployer has a lot of other features, like:
- **Easy to use** - Deployer is very easy to use. It has a simple and intuitive syntax.
- **Fast** - Deployer is very fast. It uses parallel connections to deploy your application.
- **Secure** - Deployer uses SSH to connect to your server.
- **Supports all major PHP frameworks** - Deployer supports all major PHP frameworks.

You can read more about Deployer in [Getting Started](/docs/getting-started.md).


MARKDOWN;

                $map = function (DocTask $task, $ident = '') use (&$map, $findTask, &$intro): void {
                    foreach ($task->group as $taskName) {
                        $t = $findTask($taskName);
                        if ($t !== null) {
                            $intro .= "$ident* {$t->mdLink()} – $t->desc\n";
                            if ($t->group !== null) {
                                $map($t, $ident . '  ');
                            }
                        }
                    }
                };
                $deployTask = $findTask('deploy');
                if ($deployTask !== null) {
                    $intro .= "The [deploy](#deploy) task of **$brandName** consists of:\n";
                    $map($deployTask);
                }

                $intro .= "\n\n";

                $artifactBuildTask = $findTask('artifact:build', false);
                $artifactDeployTask = $findTask('artifact:deploy', false);
                if ($artifactDeployTask !== null && $artifactBuildTask !== null) {
                    $intro .= "In addition the **$brandName** recipe contains an artifact deployment.\n";
                    $intro .= <<<MD
This is a two step process where you first execute

```php
bin/dep artifact:build [options] [localhost]
```

to build an artifact, which then is deployed on a server with

```php
bin/dep artifact:deploy [host]
```

The `localhost` to build the artifact on has to be declared local, so either add
```php
localhost()
    ->set('local', true);
```
to your deploy.php or
```yaml
hosts:
    localhost:
        local: true
```
to your deploy yaml.

The [artifact:build](#artifact:build) command of **$brandName** consists of: 
MD;
                    $map($artifactBuildTask);

                    $intro .= "\n\n The [artifact:deploy](#artifact:deploy) command of **$brandName** consists of:\n";

                    $map($artifactDeployTask);

                    $intro .= "\n\n";
                }
            }
            if (count($recipe->require) > 0) {
                if (is_framework_recipe($recipe)) {
                    $link = recipe_to_md_link($recipe->require[0]);
                    $intro .= "The $recipe->recipeName recipe is based on the $link recipe.\n";
                } else {
                    $intro .= "* Requires\n";
                    foreach ($recipe->require as $r) {
                        $link = recipe_to_md_link($r);
                        $intro .= "  * {$link}\n";
                    }
                }
            }
            if (!empty($recipe->comment)) {
                $intro .= "\n$recipe->comment\n";
            }
            if (count($recipe->config) > 0) {
                $config .= "## Configuration\n";
                foreach ($recipe->config as $c) {
                    $config .= "### {$c->name}\n";
                    $config .= "[Source](https://github.com/deployphp/deployer/blob/master/{$c->recipePath}#L{$c->lineNumber})\n\n";
                    $o = $findConfigOverride($recipe, $c->name);
                    if ($o !== null) {
                        $md = php_to_md($o->recipePath);
                        $anchor = anchor($c->name);
                        $config .= "Overrides [{$c->name}](/docs/$md#$anchor) from `$o->recipePath`.\n\n";
                    }
                    $config .= $replaceLinks($c->comment);
                    $config .= "\n";
                    if (
                        !empty($c->defaultValue)
                        && $c->defaultValue !== "''"
                        && $c->defaultValue !== '[]'
                    ) {
                        $config .= "```php title=\"Default value\"\n";
                        $config .= $c->defaultValue;
                        $config .= "\n";
                        $config .= "```\n";
                    }
                    $config .= "\n\n";
                }
            }
            if (count($recipe->tasks) > 0) {
                $tasks .= "## Tasks\n\n";
                foreach ($recipe->tasks as $t) {
                    $tasks .= "### {$t->name}\n";
                    $tasks .= "[Source](https://github.com/deployphp/deployer/blob/master/{$t->recipePath}#L{$t->lineNumber})\n\n";
                    $tasks .= add_tailing_dot($t->desc) . "\n\n";
                    $tasks .= $replaceLinks($t->comment);
                    if (is_array($t->group)) {
                        $tasks .= "\n\n";
                        $tasks .= "This task is group task which contains next tasks:\n";
                        foreach ($t->group as $taskName) {
                            $t = $findTask($taskName);
                            if ($t !== null) {
                                $tasks .= "* {$t->mdLink()}\n";
                            } else {
                                $tasks .= "* `$taskName`\n";
                            }
                        }
                    }
                    $tasks .= "\n\n";
                }
            }

            $output = <<<MD
<!-- DO NOT EDIT THIS FILE! -->
<!-- Instead edit $recipe->recipePath -->
<!-- Then run bin/docgen -->

# $title

$intro
$config
$tasks
MD;

            $filePath = "$destination/" . php_to_md($recipe->recipePath);
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }
            $output = remove_text_emoji($output);
            file_put_contents($filePath, $output);
        }
        $this->generateRecipesIndex($destination);
        $this->generateContribIndex($destination);
        return null;
    }

    public function generateRecipesIndex(string $destination) {
        $index = "# All Recipes\n\n";
        $list = [];
        foreach ($this->recipes as $recipe) {
            if (preg_match('/^recipe\/[^\/]+\.php$/', $recipe->recipePath)) {
                $name = framework_brand_name($recipe->recipeName);
                $list[] = "* [$name Recipe](/docs/recipe/{$recipe->recipeName}.md)";
            }
        }
        sort($list);
        $index .= implode("\n", $list);
        file_put_contents("$destination/recipe/README.md", $index);
    }

    public function generateContribIndex(string $destination) {
        $index = "# All Contrib Recipes\n\n";
        $list = [];
        foreach ($this->recipes as $recipe) {
            if (preg_match('/^contrib\/[^\/]+\.php$/', $recipe->recipePath)) {
                $name = ucfirst($recipe->recipeName);
                $list[] = "* [$name Recipe](/docs/contrib/$recipe->recipeName.md)";
            }
        }
        sort($list);
        $index .= implode("\n", $list);
        file_put_contents("$destination/contrib/README.md", $index);
    }
}

function trim_comment(string $line): string
{
    return preg_replace('#^(/\*\*?\s?|\s\*\s?|//\s?)#', '', $line);
}

function indent(string $text): string
{
    return implode("\n", array_map(function ($line) {
        return "  " . $line;
    }, explode("\n", $text)));
}

function php_to_md(string $file): string
{
    return preg_replace('#\.php$#', '.md', $file);
}

function anchor(string $s): string
{
    return strtolower(str_replace(':', '', $s));
}

function remove_text_emoji(string $text): string
{
    return preg_replace('/:(bowtie|smile|laughing|blush|smiley|relaxed|smirk|heart_eyes|kissing_heart|kissing_closed_eyes|flushed|relieved|satisfied|grin|wink|stuck_out_tongue_winking_eye|stuck_out_tongue_closed_eyes|grinning|kissing|kissing_smiling_eyes|stuck_out_tongue|sleeping|worried|frowning|anguished|open_mouth|grimacing|confused|hushed|expressionless|unamused|sweat_smile|sweat|disappointed_relieved|weary|pensive|disappointed|confounded|fearful|cold_sweat|persevere|cry|sob|joy|astonished|scream|neckbeard|tired_face|angry|rage|triumph|sleepy|yum|mask|sunglasses|dizzy_face|imp|smiling_imp|neutral_face|no_mouth|innocent|alien|yellow_heart|blue_heart|purple_heart|heart|green_heart|broken_heart|heartbeat|heartpulse|two_hearts|revolving_hearts|cupid|sparkling_heart|sparkles|star|star2|dizzy|boom|collision|anger|exclamation|question|grey_exclamation|grey_question|zzz|dash|sweat_drops|notes|musical_note|fire|hankey|poop|shit|\+1|thumbsup|\-1|thumbsdown|ok_hand|punch|facepunch|fist|v|wave|hand|raised_hand|open_hands|point_up|point_down|point_left|point_right|raised_hands|pray|point_up_2|clap|muscle|metal|fu|walking|runner|running|couple|family|two_men_holding_hands|two_women_holding_hands|dancer|dancers|ok_woman|no_good|information_desk_person|raising_hand|bride_with_veil|person_with_pouting_face|person_frowning|bow|couplekiss|couple_with_heart|massage|haircut|nail_care|boy|girl|woman|man|baby|older_woman|older_man|person_with_blond_hair|man_with_gua_pi_mao|man_with_turban|construction_worker|cop|angel|princess|smiley_cat|smile_cat|heart_eyes_cat|kissing_cat|smirk_cat|scream_cat|crying_cat_face|joy_cat|pouting_cat|japanese_ogre|japanese_goblin|see_no_evil|hear_no_evil|speak_no_evil|guardsman|skull|feet|lips|kiss|droplet|ear|eyes|nose|tongue|love_letter|bust_in_silhouette|busts_in_silhouette|speech_balloon|thought_balloon|feelsgood|finnadie|goberserk|godmode|hurtrealbad|rage1|rage2|rage3|rage4|suspect|trollface|sunny|umbrella|cloud|snowflake|snowman|zap|cyclone|foggy|ocean|cat|dog|mouse|hamster|rabbit|wolf|frog|tiger|koala|bear|pig|pig_nose|cow|boar|monkey_face|monkey|horse|racehorse|camel|sheep|elephant|panda_face|snake|bird|baby_chick|hatched_chick|hatching_chick|chicken|penguin|turtle|bug|honeybee|ant|beetle|snail|octopus|tropical_fish|fish|whale|whale2|dolphin|cow2|ram|rat|water_buffalo|tiger2|rabbit2|dragon|goat|rooster|dog2|pig2|mouse2|ox|dragon_face|blowfish|crocodile|dromedary_camel|leopard|cat2|poodle|paw_prints|bouquet|cherry_blossom|tulip|four_leaf_clover|rose|sunflower|hibiscus|maple_leaf|leaves|fallen_leaf|herb|mushroom|cactus|palm_tree|evergreen_tree|deciduous_tree|chestnut|seedling|blossom|ear_of_rice|shell|globe_with_meridians|sun_with_face|full_moon_with_face|new_moon_with_face|new_moon|waxing_crescent_moon|first_quarter_moon|waxing_gibbous_moon|full_moon|waning_gibbous_moon|last_quarter_moon|waning_crescent_moon|last_quarter_moon_with_face|first_quarter_moon_with_face|moon|earth_africa|earth_americas|earth_asia|volcano|milky_way|partly_sunny|octocat|squirrel|bamboo|gift_heart|dolls|school_satchel|mortar_board|flags|fireworks|sparkler|wind_chime|rice_scene|jack_o_lantern|ghost|santa|christmas_tree|gift|bell|no_bell|tanabata_tree|tada|confetti_ball|balloon|crystal_ball|cd|dvd|floppy_disk|camera|video_camera|movie_camera|computer|tv|iphone|phone|telephone|telephone_receiver|pager|fax|minidisc|vhs|sound|speaker|mute|loudspeaker|mega|hourglass|hourglass_flowing_sand|alarm_clock|watch|radio|satellite|loop|mag|mag_right|unlock|lock|lock_with_ink_pen|closed_lock_with_key|key|bulb|flashlight|high_brightness|low_brightness|electric_plug|battery|calling|email|mailbox|postbox|bath|bathtub|shower|toilet|wrench|nut_and_bolt|hammer|seat|moneybag|yen|dollar|pound|euro|credit_card|money_with_wings|e-mail|inbox_tray|outbox_tray|envelope|incoming_envelope|postal_horn|mailbox_closed|mailbox_with_mail|mailbox_with_no_mail|door|smoking|bomb|gun|hocho|pill|syringe|page_facing_up|page_with_curl|bookmark_tabs|bar_chart|chart_with_upwards_trend|chart_with_downwards_trend|scroll|clipboard|calendar|date|card_index|file_folder|open_file_folder|scissors|pushpin|paperclip|black_nib|pencil2|straight_ruler|triangular_ruler|closed_book|green_book|blue_book|orange_book|notebook|notebook_with_decorative_cover|ledger|books|bookmark|name_badge|microscope|telescope|newspaper|football|basketball|soccer|baseball|tennis|8ball|rugby_football|bowling|golf|mountain_bicyclist|bicyclist|horse_racing|snowboarder|swimmer|surfer|ski|spades|hearts|clubs|diamonds|gem|ring|trophy|musical_score|musical_keyboard|violin|space_invader|video_game|black_joker|flower_playing_cards|game_die|dart|mahjong|clapper|memo|pencil|book|art|microphone|headphones|trumpet|saxophone|guitar|shoe|sandal|high_heel|lipstick|boot|shirt|tshirt|necktie|womans_clothes|dress|running_shirt_with_sash|jeans|kimono|bikini|ribbon|tophat|crown|womans_hat|mans_shoe|closed_umbrella|briefcase|handbag|pouch|purse|eyeglasses|fishing_pole_and_fish|coffee|tea|sake|baby_bottle|beer|beers|cocktail|tropical_drink|wine_glass|fork_and_knife|pizza|hamburger|fries|poultry_leg|meat_on_bone|spaghetti|curry|fried_shrimp|bento|sushi|fish_cake|rice_ball|rice_cracker|rice|ramen|stew|oden|dango|egg|bread|doughnut|custard|icecream|ice_cream|shaved_ice|birthday|cake|cookie|chocolate_bar|candy|lollipop|honey_pot|apple|green_apple|tangerine|lemon|cherries|grapes|watermelon|strawberry|peach|melon|banana|pear|pineapple|sweet_potato|eggplant|tomato|corn|house|house_with_garden|school|office|post_office|hospital|bank|convenience_store|love_hotel|hotel|wedding|church|department_store|european_post_office|city_sunrise|city_sunset|japanese_castle|european_castle|tent|factory|tokyo_tower|japan|mount_fuji|sunrise_over_mountains|sunrise|stars|statue_of_liberty|bridge_at_night|carousel_horse|rainbow|ferris_wheel|fountain|roller_coaster|ship|speedboat|boat|sailboat|rowboat|anchor|rocket|airplane|helicopter|steam_locomotive|tram|mountain_railway|bike|aerial_tramway|suspension_railway|mountain_cableway|tractor|blue_car|oncoming_automobile|car|red_car|taxi|oncoming_taxi|articulated_lorry|bus|oncoming_bus|rotating_light|police_car|oncoming_police_car|fire_engine|ambulance|minibus|truck|train|station|train2|bullettrain_front|bullettrain_side|light_rail|monorail|railway_car|trolleybus|ticket|fuelpump|vertical_traffic_light|traffic_light|warning|construction|beginner|atm|slot_machine|busstop|barber|hotsprings|checkered_flag|crossed_flags|izakaya_lantern|moyai|circus_tent|performing_arts|round_pushpin|triangular_flag_on_post|jp|kr|cn|us|fr|es|it|ru|gb|uk|de|one|two|three|four|five|six|seven|eight|nine|keycap_ten|1234|zero|hash|symbols|arrow_backward|arrow_down|arrow_forward|arrow_left|capital_abcd|abcd|abc|arrow_lower_left|arrow_lower_right|arrow_right|arrow_up|arrow_upper_left|arrow_upper_right|arrow_double_down|arrow_double_up|arrow_down_small|arrow_heading_down|arrow_heading_up|leftwards_arrow_with_hook|arrow_right_hook|left_right_arrow|arrow_up_down|arrow_up_small|arrows_clockwise|arrows_counterclockwise|rewind|fast_forward|information_source|ok|twisted_rightwards_arrows|repeat|repeat_one|new|top|up|cool|free|ng|cinema|koko|signal_strength|u5272|u5408|u55b6|u6307|u6708|u6709|u6e80|u7121|u7533|u7a7a|u7981|sa|restroom|mens|womens|baby_symbol|no_smoking|parking|wheelchair|metro|baggage_claim|accept|wc|potable_water|put_litter_in_its_place|secret|congratulations|m|passport_control|left_luggage|customs|ideograph_advantage|cl|sos|id|no_entry_sign|underage|no_mobile_phones|do_not_litter|non-potable_water|no_bicycles|no_pedestrians|children_crossing|no_entry|eight_spoked_asterisk|eight_pointed_black_star|heart_decoration|vs|vibration_mode|mobile_phone_off|chart|currency_exchange|aries|taurus|gemini|cancer|leo|virgo|libra|scorpius|sagittarius|capricorn|aquarius|pisces|ophiuchus|six_pointed_star|negative_squared_cross_mark|a|b|ab|o2|diamond_shape_with_a_dot_inside|recycle|end|on|soon|clock1|clock130|clock10|clock1030|clock11|clock1130|clock12|clock1230|clock2|clock230|clock3|clock330|clock4|clock430|clock5|clock530|clock6|clock630|clock7|clock730|clock8|clock830|clock9|clock930|heavy_dollar_sign|copyright|registered|tm|x|heavy_exclamation_mark|bangbang|interrobang|o|heavy_multiplication_x|heavy_plus_sign|heavy_minus_sign|heavy_division_sign|white_flower|100|heavy_check_mark|ballot_box_with_check|radio_button|link|curly_loop|wavy_dash|part_alternation_mark|trident|black_square|white_square|white_check_mark|black_square_button|white_square_button|black_circle|white_circle|red_circle|large_blue_circle|large_blue_diamond|large_orange_diamond|small_blue_diamond|small_orange_diamond|small_red_triangle|small_red_triangle_down|shipit):/i', ':&#8203;\1:', $text);
}

function add_tailing_dot(string $sentence): string
{
    if (empty($sentence)) {
        return $sentence;
    }
    if (str_ends_with($sentence, '.')) {
        return $sentence;
    }
    return $sentence . '.';
}

function recipe_to_md_link(string $recipe): string
{
    $md = php_to_md($recipe);
    $basename = basename($recipe, '.php');
    return "[$basename](/docs/$md)";
}

function is_framework_recipe(DocRecipe $recipe): bool
{
    return preg_match('/recipe\/[\w_\d]+\.php$/', $recipe->recipePath) &&
    !in_array($recipe->recipeName, ['common', 'composer', 'provision'], true);
}

function framework_brand_name(string $brandName): string
{
    $brandName = preg_replace('/(\w+)(\d)/', '$1 $2', $brandName);
    $brandName = preg_replace('/typo 3/', 'TYPO3', $brandName);
    $brandName = preg_replace('/yii/', 'Yii2', $brandName);
    $brandName = preg_replace('/wordpress/', 'WordPress', $brandName);
    $brandName = preg_replace('/_/', ' ', $brandName);
    $brandName = preg_replace('/framework/', 'Framework', $brandName);
    return ucfirst($brandName);
}
