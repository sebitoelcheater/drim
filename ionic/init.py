import sys
from subprocess import call
import os, errno
import yaml

args = sys.argv[1:]
DRIM_PATH = args[0]
PROJECT_PATH = args[1]

project_drim_path = "{}/{}".format(PROJECT_PATH, ".drim")
project_keys_path = "{}/{}/{}".format(PROJECT_PATH, ".drim", "keys")

if not os.path.exists(project_drim_path):
    os.makedirs(project_drim_path)
if not os.path.exists(project_keys_path):
    os.makedirs(project_keys_path)

platforms = {}

app_name = input('Application name: ')

android = input('Include Android? (Y/n): ') in ["", "y", "Y"]
if android:
    print("Creating keystore...")
    release_keystore_path = "{}/{}-{}".format(project_keys_path, app_name, "release-key.keystore")
    create_keystore = call([
        'keytool', '-genkey', '-v', '-keystore', release_keystore_path,
        '-alias', "{}_{}".format(app_name, 'key'), '-keyalg', 'RSA', '-keysize', '2048', '-validity', '10000'
    ])
    password = input('Enter keystore password again: ')
    platforms["android"] = {
        "present": True,
        "keystore": {
            "password": password,
            "path": release_keystore_path,
            "alias": "{{ app_name }}_key",
        }
    }
else:
    platforms["android"] = False


ios = input('Include iOS? (Y/n)') in ["", "y", "Y"]
platforms["ios"] = {
    "present": False
}
browser = input('Include Web Browser? (Y/n)') in ["", "y", "Y"]
if browser:
    print("Enter your production VPS credentials: ")
    platforms["browser"] = {
        "present": True,
        "ip": input('IP: '),
        "user": input('User: '),
        "private_key": input('Private key file path: '),
    }
else:
    platforms["browser"] = {"present": False}


with open("{}/{}".format(project_drim_path, 'credentials.yml'), 'w') as outfile:
    data = {
        "platforms": platforms,
        "app_name": app_name,
    }
    yaml.dump(data, outfile, default_flow_style=False)
