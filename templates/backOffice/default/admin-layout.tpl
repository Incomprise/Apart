{* -- By default, check admin login ----------------------------------------- *}

{block name="check-auth"}
    {check_auth role="ADMIN" resource="{block name="check-resource"}{/block}" module="{block name="check-module"}{/block}" access="{block name="check-access"}{/block}" login_tpl="/admin/login"}
{/block}

{* -- Define some stuff for Smarty ------------------------------------------ *}
{config_load file='variables.conf'}

{* -- Declare assets directory, relative to template base directory --------- *}
{declare_assets directory='assets'}

{* Set the default translation domain, that will be used by {intl} when the 'd' parameter is not set *}
{default_translation_domain domain='bo.default'}

{block name="no-return-functions"}{/block}

<!DOCTYPE html>
<html lang="{$lang_code}">
<head>
    <meta charset="utf-8">

    <title>{block name="page-title"}Default Page Title{/block} - {intl l='Thelia Back Office'}</title>

    <link rel="shortcut icon" href="{image file='assets/img/favicon.ico'}" />

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    {block name="meta"}{/block}

    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400italic,600,700%7COpen+Sans:300,400,400italic,600,700">
    {* -- Bootstrap CSS section --------------------------------------------- *}

    {block name="before-bootstrap-css"}{/block}

    <link rel="stylesheet" href="{stylesheet file='assets/css/styles.css'}">

    {block name="after-bootstrap-css"}{/block}

    {* -- Admin CSS section ------------------------------------------------- *}

    {block name="before-admin-css"}{/block}

    {block name="after-admin-css"}{/block}

    {* Modules css are included here *}

    {hook name="main.head-css" location="head_css" }

    {* HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries *}
    <!--[if lt IE 9]>
    <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
    {javascripts file='assets/js/libs/respond.min.js'}
    <script src="{$asset_url}"></script>
    {/javascripts}
    <![endif]-->
</head>

<body>
    <div id="wrapper">

        {* display top bar only if admin is connected *}
        {loop name="top-bar-auth" type="auth" role="ADMIN"}

            {* -- Brand bar section ------------------------------------------------- *}

            {hook name="main.before-topbar" location="before_topbar" }

            <!-- Navigation -->
            <nav class="navbar navbar-default navbar-static-top" role="navigation">
                <div class="navbar-top">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
                            <span class="sr-only">Toggle navigation</span>
                            <span></span>
                        </button>
                        <a class="navbar-brand" href="{url path='/admin/home'}">
                            thelia
                        </a>
                    </div>
                    <!-- /.navbar-header -->

                    {loop name="top-bar-search" type="auth" role="ADMIN" resource="admin.search" access="VIEW"}
                        <div class="nav navbar-search navbar-left">
                            {strip}
                                <form action="{url path='/admin/search'}">
                                    <input type="text" id="search_term" name="search_term" placeholder="{intl l='Search...'}" value="{if isset($smarty.get.search_term)}{trim($smarty.get.search_term)}{/if}">
                                    <button type="submit"><i class="fas fa-search"></i></button>
                                </form>
                            {/strip}
                        </div>
                    {/loop}

                    <ul class="nav navbar-top-links navbar-right">
                        {hook name="main.topbar-top" }
                        <li class="dropdown">
                            {loop type="lang" name="ui-lang" id={lang attr='id'} backend_context="1"}
                                <button class="dropdown-toggle" data-toggle="dropdown">
                                    <img class="flags" src="{image file="assets/img/svgFlags/{$CODE}.svg"}" alt="{$TITLE}" />
                                </button>
                            {/loop}

                            <ul class="dropdown-menu">
                                {loop type="lang" name="ui-lang" backend_context="1"}
                                    <li>
                                        <a href="{url path="{navigate to="current"}" lang={$CODE}}">
                                            <img class="flags" src="{image file="assets/img/svgFlags/{$CODE}.svg"}" alt="{$TITLE}" />
                                        </a>
                                    </li>
                                {/loop}
                            </ul>
                        </li>

                        <li>
                            <a href="{url path='admin/configuration/administrators/view'}" title="{intl l="Edit profile"}">
                                {admin attr="firstname"} {admin attr="lastname"}
                            </a>
                        </li>
                        <li>
                            <a href="{navigate to="index"}" title="{intl l='View site'}" target="_blank"><i class="far fa-eye"></i></a>
                        </li>
                        <li>
                            <a class="logout" href="{url path='admin/logout'}" title="{intl l="Logout"}">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        </li>
                    </ul>
                    <!-- /.navbar-top-links -->
                </div>


                <div class="navbar-default sidebar" role="navigation">
                    <div class="sidebar-nav navbar-collapse">

                        {include file="includes/main-menu.html"}

                        {hook name="main.inside-topbar" location="inside_topbar" }
                    </div>
                    <!-- /.sidebar-collapse -->
                </div>
                <!-- /.navbar-static-side -->

                {hook name="main.after-topbar" location="after_topbar" }
            </nav>

            <div id="page-wrapper">

                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">{block name="page-title"}{/block}</h1>
                    </div>
                    <!-- /.col-lg-12 -->
                </div>
                <!-- /.row -->

                {* -- Main page content section ----------------------------------------- *}
                {hook name="main.before-content" location="before_content"}

                <div class="row">
                    {block name="main-content"}Put here the content of the template{/block}
                </div>

                {hook name="main.after-content" location="after_content"}

            </div>
        {/loop}

        {hook name="main.before-footer" location="before_footer" }

        <footer class="footer">
            <div>
                <p><strong>&copy; Thelia <time datetime="{'Y-m-d'|date}">{'Y'|date}</time></strong>
                - {intl l='Version %ver' ver="{$THELIA_VERSION}"}
                - <a href="http://www.openstudio.fr/" target="_blank">{intl l='Made with ???? by OpenStudio'}</a>
                - <a href="http://thelia.net/forum" target="_blank">{intl l='Need help ?'}</a>
                </p>

                {hook name="main.in-footer" location="in_footer" }

            </div>
            <ul id="follow-us" class="list-unstyled list-inline">
                <li>
                    <a href="https://twitter.com/theliaecommerce" target="_blank">
                        <i class="fab fa-twitter"></i>
                    </a>
                </li>
                <li>
                    <a href="https://www.facebook.com/theliaecommerce" target="_blank">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </li>
                <li>
                    <a href="https://github.com/thelia/thelia" target="_blank">
                        <i class="fab fa-github"></i>
                    </a>
                </li>
            </ul>
        </footer>

        {hook name="main.after-footer" location="after_footer" }
    </div> <!-- #wrapper -->

	{* -- Javascript section ------------------------------------------------ *}

	{block name="before-javascript-include"}{/block}
    <script src="//code.jquery.com/jquery-2.0.3.min.js"></script>
    <script>
        if (typeof jQuery == 'undefined') {
            {javascripts file='assets/js/libs/jquery.js'}
            document.write(unescape("%3Cscript src='{$asset_url}' %3E%3C/script%3E"));
            {/javascripts}
        }
    </script>

	{block name="after-javascript-include"}{/block}

    {javascripts file='assets/js/bootstrap/bootstrap.js'}
        <script src="{$asset_url}"></script>
    {/javascripts}

    {javascripts file='assets/js/libs/jquery.toolbar.min.js'}
        <script src="{$asset_url}"></script>
    {/javascripts}

    {javascripts file='assets/js/libs/metis-menu.min.js'}
        <script src="{$asset_url}"></script>
    {/javascripts}

    {block name="javascript-initialization"}{/block}

    {javascripts file='assets/js/main.js'}
        <script src="{$asset_url}"></script>
    {/javascripts}

	{* Modules scripts are included now *}
    {hook name='main.footer-js' location="footer_js"}

    {block name="javascript-last-call"}{/block}
    </body>
</html>
