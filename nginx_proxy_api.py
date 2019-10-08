
# Tea Inside internal nginx_prox API.

import requests
from requests.auth import AuthBase

token = "EcppB0QIiAaJ/fy6nQxhIvFPbAquTZ162mCbCLL3ooJ0THiQ+/T1xPYLsTLnRBVJfwAvDCVh9C2+"

class TokenAuth(AuthBase):
    def __init__(self, token):
        self.token = token

    def __call__(self, r):
        r.headers['Authorization'] = f'{self.token}'
        return r

def tea_ipton_resolve(a, b, c, d):
	return hex(d | (c << 8) | (b << 16) | (a << 24))



# Set domain IP to 139.180.212.115
target_ip = "139.180.212.115"
target_ip = target_ip.split(".")

res = requests.post(
	'http://127.0.0.1:8000/internal.php',
	auth=TokenAuth(token),
	params={
		'action': 'nginx_prox',
		'domain': 'starlight.teainside.org',
		'sb_act': 'set',
		'set_target': tea_ipton_resolve(int(target_ip[0]), int(target_ip[1]), int(target_ip[2]), int(target_ip[3]))
	}
)

json = res.text

print(json)