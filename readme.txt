wpLDAP is a LDAP/AD authentication plugin for Wordpress 2.0+.

Current Version: 1.02

It uses the famous adLDAP class to do all the dirty LDAP/AD work.

Instructions to Install.

1. Download the file wpLDAP.zip (Located at http://onlymetallica.com/ashay/blog/?page_id=133) and unzip it in the wp-content/plugins folder in wordpress.
2. Sign in as ‘admin’ and go to options > wpLDAP Options
3. Add the following details
3.1 Domain Controllers: This is the AD server address which the authentication scripts looks for. Multiple entries can be sepearted by commas - e.g. looks like 101.11.11.22, my.ldapserver.com, 10.10.10.22
3.2 Base DN for the AD server: e.g looks like CN=Users,DC=domain,DC=com
3.3 Account Suffix: Many a times the usernames are email addresses in systems. This field lets you add the default suffix to usernames when authenticating the user. hence if my username was ashay@domain.com. I can add @domain.com in the suffix field and sign in as ‘ashay’
3.4 Enable LDAP: Lets you activate or de-activate this plugin.
3.5 The last option lets you add a new user in the Wordpress user database so that the admins can have better access control on them through the Wordpress Admin System. The users will still be authenticated through AD/LDAP. The users are added in the wordpress db on first signon and will inherit the default role specified in Wordpress admin Option > General > New User Default Role.

Hope you guys enjoy this plugin!. And do leave you feedback if something doesn’t work or if you need help. 