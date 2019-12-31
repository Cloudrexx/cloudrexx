# Set the path, relative from the document root, to Contrexx.
# Technical note: This is ASCMS_PATH_OFFSET
# I.e.: /
RewriteBase   %PATH_ROOT_OFFSET%

# Deny direct access to directories containing sensitive data
RewriteCond %{ENV:REDIRECT_END} !1
RewriteCond %{REQUEST_URI} ^/(config|tmp|websites|core/.*/Data|core_modules/.*/Data|modules/.*/Data)/
RewriteRule . - [F]

# MultiSite: Map requests to MEDIA RESOURCES of the Code Base of a Website
RewriteCond %{ENV:REDIRECT_END} !1
RewriteCond %{REQUEST_URI} ^/(core|core_modules|lib|modules)/
RewriteRule (.*) ${websiteDomainCodeBaseMap:%{HTTP_HOST}}/$1 [L,QSA,E=END:1]

# MultiSite: Map requests to CONTENT DATA of a Website
RewriteCond %{ENV:REDIRECT_END} !1
RewriteCond %{REQUEST_URI} ^/(feed|media|images)/
RewriteRule (.*) ${websiteDomainContentMap:%{HTTP_HOST}}/$1 [L,QSA,E=END:1]

# MultiSite: Try to map the request of a webdesign template file to the
#            website's data repository.
#            Replace '%PATH_DOCUMENT_ROOT%' by the absolute path to the
#            document root of the Contrexx installation. I.e.: /var/www
RewriteCond %{ENV:REDIRECT_END} !1
RewriteCond %{REQUEST_URI} ^/(themes)/
RewriteCond %PATH_DOCUMENT_ROOT%${websiteDomainContentMap:%{HTTP_HOST}}%{REQUEST_URI} -f
RewriteRule (.*) ${websiteDomainContentMap:%{HTTP_HOST}}/$1 [L,QSA,E=END:1]

# MultiSite: Try to map the request of a the webdesign template file to
#            the website's Code Base.
#            Replace '%PATH_DOCUMENT_ROOT%' by the absolute path to the
#            document root of the Contrexx installation. I.e.: /var/www
RewriteCond %{ENV:REDIRECT_END} !1
RewriteCond %{REQUEST_URI} ^/(themes)/
RewriteCond %PATH_DOCUMENT_ROOT%${websiteDomainCodeBaseMap:%{HTTP_HOST}}%{REQUEST_URI} -f
RewriteRule (.*) ${websiteDomainCodeBaseMap:%{HTTP_HOST}}/$1 [L,QSA,E=END:1]

# Resolve language specific sitemap.xml
RewriteRule ^([a-z]{1,2}(?:-[A-Za-z]{2,4})?)\/sitemap.xml$ sitemap_$1.xml [L,NC]

# Allow directory index files
RewriteCond %{REQUEST_FILENAME}/index.php -f
RewriteRule   .  %{REQUEST_URI}/index.php [L,QSA]

# Redirect all requests to non-existing files to Contrexx
RewriteCond   %{REQUEST_FILENAME}  !-f
RewriteRule   .  index.php?__cap=%{REQUEST_URI} [L,QSA]

# Add captured request to index files
RewriteRule ^index.php index.php?__cap=%{REQUEST_URI} [L,QSA]
