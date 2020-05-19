import sys
import subprocess
import os, errno
import time
import yaml
from pathlib import Path
import string
import random
from jinja2 import Template
import json
import socket
from os.path import expanduser

HOME = str(Path.home())
args = sys.argv[1:]
DRIM_PATH = args[0]
PROJECT_PATH = args[1]
DIGITALOCEAN_SIZES = 'Class\tSlug\tvCPUs\tRAM\tDisk\tTransfer\tMonthly Price\n' \
                     'Standard\ts-1vcpu-1gb\t1\t1 GB\t25 GB\t1 TB\t$5\n' \
                     'Standard\ts-1vcpu-2gb\t1\t2 GB\t50 GB\t2 TB\t$10\n' \
                     'Standard\ts-1vcpu-3gb\t1\t3 GB\t60 GB\t3 TB\t$15\n' \
                     'Standard\ts-2vcpu-2gb\t2\t2 GB\t60 GB\t3 TB\t$15\n' \
                     'Standard\ts-3vcpu-1gb\t3\t1 GB\t60 GB\t3 TB\t$15\n' \
                     'Standard\ts-2vcpu-4gb\t2\t4 GB\t80 GB\t4 TB\t$20\n' \
                     'Standard\ts-4vcpu-8gb\t4\t8 GB\t160 GB\t5 TB\t$40\n' \
                     'Standard\ts-6vcpu-16gb\t6\t16 GB\t320 GB\t6 TB\t$80\n' \
                     'Standard\ts-8vcpu-32gb\t8\t32 GB\t640 GB\t7 TB\t$160\n' \
                     'Standard\ts-12vcpu-48gb\t12\t48 GB\t960 GB\t8 TB\t$240\n' \
                     'Standard\ts-16vcpu-64gb\t16\t64 GB\t1,280 GB\t9 TB\t$320\n' \
                     'Standard\ts-20vcpu-96gb\t20\t96 GB\t1,920 GB\t10 TB\t$480\n' \
                     'Standard\ts-24vcpu-128gb\t24\t128 GB\t2,560 GB\t11 TB\t$640\n' \
                     'Standard\ts-32vcpu-192gb\t32\t192 GB\t3,840 GB\t12 TB\t$960\n'

project_drim_path = "{}/{}".format(PROJECT_PATH, ".drim")
project_keys_path = "{}/{}/{}".format(PROJECT_PATH, ".drim", "keys")

if not os.path.exists(project_drim_path):
    os.makedirs(project_drim_path)
if not os.path.exists(project_keys_path):
    os.makedirs(project_keys_path)

json_vars = {}

app_name = input('Application name: ')
json_vars["name"] = app_name
json_vars["user"] = "{{name}}_user"
json_vars["project_folder"] = "/home/{{user}}/{{name}}"
json_vars["project_root"] = "/home/{{user}}/{{name}}/{{name}}"
json_vars["localhost_project_folder"] = PROJECT_PATH
json_vars["localhost_virtualenv"] = "~/.virtualenvs/{{name}}"
json_vars["project_repo"] = "git@github.com:sebitoelcheater/{{name}}.git"
json_vars["users"] = [
    {
        "name": "{{name}}_user",
        "comment": "{{name}} user",
        "group": "admin",
        "password": ''.join(random.choice(string.ascii_uppercase + string.digits + string.ascii_lowercase) for _ in range(32))
    }
]
json_vars["sysadmin"] = {
    "name": input('Sysadmin name: '),
    "email": input('Sysadmin email: '),
}
json_vars["databases"] = {
    "postgresql": []
}
db_names = {}
for i in range(int(input('Number of PostgreSQL databases: '))):
    db_names[i] = input("Name of database number {}: ".format(i+1))
    json_vars["databases"]["postgresql"].append({
        "database": db_names[i],
        "user": "{}_admin".format(db_names[i]),
        "password": "{}_12345678".format(db_names[i]),
        "host": "localhost",
        "port": '5432',
        "postgis": True,
    })

if input('Create MySQL database? (Y/n): ') in ["", "y", "Y"]:
    mysql_db_name = input('Name of MySQL database: ')
    json_vars["mysql_root_password"] = ''.join(
        random.choice(string.ascii_uppercase + string.digits + string.ascii_lowercase) for _ in range(16))
    json_vars["mysql_databases"] = [
        {'name': mysql_db_name, 'encoding': 'utf8'}
    ]
    json_vars["mysql_users"] = [
        {
            'name': f'{mysql_db_name}_user',
            'host': "%",
            'password': ''.join(
                random.choice(string.ascii_uppercase + string.digits + string.ascii_lowercase) for _ in range(16)),
            'priv': f'{mysql_db_name}.*:ALL'
        }
    ]

SERVER_URL = input('Server URL: ')
if SERVER_URL != '':
    json_vars["server_url"] = SERVER_URL

CERTBOT_EMAIL = input('Certbot Email: ')
if CERTBOT_EMAIL != '':
    json_vars["certbot"] = {'email': CERTBOT_EMAIL}

if input('Create bucket? (Y/n): ') in ["", "y", "Y"]:
    bucket_name = "{}-{}".format(app_name, "bucket")
    subprocess.check_output(["aws", "s3", "mb", "s3://{}".format(bucket_name)])
    with open("{}/digitalocean/templates/aws.s3.policy.json".format(DRIM_PATH), 'r') as myfile:
        template = Template(myfile.read().replace('\n', ''))
        policy = template.render(name=bucket_name)
    new_policy = json.loads(subprocess.check_output(["aws", "iam", "create-policy", "--policy-name", "{}S3".format(app_name), "--policy-document", policy]))
    subprocess.check_output(["aws", "iam", "create-user", "--user-name", app_name])
    aws_keys = json.loads(subprocess.check_output(["aws", "iam", "create-access-key", "--user-name", app_name]))
    subprocess.check_output(["aws", "iam", "attach-user-policy", "--user-name", app_name, "--policy-arn", new_policy["Policy"]["Arn"]])
    json_vars["aws"] = {
        "STORAGE_BUCKET_NAME": bucket_name,
        "ACCESS_KEY_ID": aws_keys["AccessKey"]["AccessKeyId"],
        "SECRET_ACCESS_KEY": aws_keys["AccessKey"]["SecretAccessKey"],
    }

if input('Add sendgrid? (Y/n): ') in ["", "y", "Y"]:
    heroku_name = app_name
    subprocess.check_output(["heroku", "apps:create", heroku_name])
    subprocess.check_output(["heroku", "addons:create", "sendgrid:starter", "--app", heroku_name])
    sendgrid_password = str(subprocess.check_output(["heroku", "config:get", "SENDGRID_PASSWORD", "--app", heroku_name])).replace("\\n'",'').replace("b'", "")
    sendgrid_username = str(subprocess.check_output(["heroku", "config:get", "SENDGRID_USERNAME", "--app", heroku_name])).replace("\\n'",'').replace("b'", "")
    json_vars["sendgrid"] = {
        "username": sendgrid_username,
        "password": sendgrid_password,
    }

if input('Add Google Maps? (Y/n): ') in ["", "y", "Y"]:
    json_vars["google_maps"] = {"api_key": "AIzaSyB0Ycb0-W0SoyQ8AzosjQGzGQ9yg1q8kKo"}

if input('Create DigitalOcean Droplet? (Y/n): ') in ["", "y", "Y"]:
    import digitalocean
    from digitalocean import SSHKey
    do_token = input('DigitalOcean token: ')
    manager = digitalocean.Manager(token=do_token)
    keys = manager.get_all_sshkeys()
    if len(keys) == 0:
        print('uploading your private key to digitalocean...')
        user_ssh_key = open("{}/{}".format(PROJECT_PATH, input('Public key path: '))).read()
        key = SSHKey(token=do_token, name=socket.gethostname(), public_key=user_ssh_key)
        key.create()
        keys = manager.get_all_sshkeys()
    print('creating droplet...')
    droplet = digitalocean.Droplet(
        token=do_token,
        name=input('Droplet name: '),
        region='sfo2',  # Amsterdam
        image='ubuntu-16-04-x64',  # Ubuntu 16.04 x64
        size_slug=input(f'{DIGITALOCEAN_SIZES}\nDroplet size: '),  # 512MB
        ssh_keys=keys,  # Automatic conversion
        backups=False)
    droplet.create()
    actions = droplet.get_actions()
    action = actions[0]
    while action.status == 'in-progress':
        time.sleep(2)
        action.load()
    if action.status == 'completed':
        droplet.load()
        DROPLET_IP = droplet.ip_address
    print(action.status)

with open("{}/{}".format(project_drim_path, 'vars.yml'), 'w') as outfile:
    yaml.dump(json_vars, outfile, default_flow_style=False)

# hosts.yml

root_host = "{} ansible_ssh_user={} ansible_ssh_private_key_file={} ansible_sudo_pass={}".format(
    DROPLET_IP if 'DROPLET_IP' in locals() else input('Server IP: '),
    "root",
    input('Private key path: '),
    json_vars["users"][0]["password"]
)

with open("{}/{}".format(project_drim_path, "hosts.yml"), "w") as f:
    f.writelines(["[digitalocean_root]\n", root_host+"\n", "[digitalocean]\n"])
