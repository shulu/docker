set :application, "ciyocon"
set :domain,      ""
set :deploy_to,   "/data/web/#{application}"
set :app_path,    "app"
set :symfony_console, app_path + "/api/console"

set :repository,  "ssh://git@118.89.20.93:19901/ciyocon/lychee-server.git"
set :scm,         :git
# Or: `accurev`, `bzr`, `cvs`, `darcs`, `subversion`, `mercurial`, `perforce`, or `none`

set :model_manager, "doctrine"
# Or: `propel`

role :web,        domain                         # Your HTTP server, Apache/etc
role :app,        domain, :primary => true       # This may be the same as your `Web` server

set  :keep_releases,  3

set  :user,           "deployer"
set  :use_sudo,       false
set  :deploy_via,     :remote_cache
set  :cache_warmup,   false
set  :composer_bin,   "/usr/local/bin/composer"

default_run_options[:pty] = true
#ssh_options[:port] = "22127"
ssh_options[:forward_agent] = true

set :shared_files,      ["app/config/parameters.yml", "app/api/config/parameters.yml", "app/website/config/parameters.yml", "app/admin/config/parameters.yml"]
# set :shared_children,     [app_path + "/logs"]
set :shared_children,     ["upload","client_logs", "game_records"]
set :use_composer, true
set :update_vendors, false
set :copy_vendors, true
# set :writable_dirs,       [app_path + "/cache", app_path + "/logs"]
set :writable_dirs,       []
set :composer_options,  "--no-dev -vvv --verbose --prefer-dist --optimize-autoloader"

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL

before 'symfony:composer:install', 'composer:copy_vendors'
before 'symfony:composer:update', 'composer:copy_vendors'

namespace :composer do
  task :copy_vendors, :except => { :no_release => true } do
    capifony_pretty_print "--> Copy vendor file from previous release"

    run "vendorDir=#{current_path}/vendor; if [ -d $vendorDir ] || [ -h $vendorDir ]; then cp -a $vendorDir #{latest_release}/; fi;"
    run "composerLock=#{current_path}/composer.lock; if [ -f $composerLock ]; then cp -a $composerLock #{latest_release}/; fi;"
    capifony_puts_ok
  end
end

