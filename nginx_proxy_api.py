
# Tea Inside internal nginx_prox API.

import requests
from requests.auth import AuthBase

class TokenAuth(AuthBase):
    def __init__(self, token):
        self.token = token

    def __call__(self, r):
        r.headers['Authorization'] = f'{self.token}'
        return r

class SimpleTeaProxDomainResolver:
	def __init__(self, token, domain):
		self.token = token
		self.domain = domain

	# Build Tea Inside ipton data
	@staticmethod
	def tea_ipton_resolve(a, b, c, d):
		return hex(d | (c << 8) | (b << 16) | (a << 24))

	# Set domain IP.
	def set_ip(self, target_ip):
		target_ip = target_ip.split(".")
		return requests.post(
			'https://api.teainside.org/internal.php',
			auth=TokenAuth(token),
			params={
				'action': 'nginx_prox',
				'domain': self.domain,
				'sb_act': 'set',
				'set_target': self.tea_ipton_resolve(int(target_ip[0]), int(target_ip[1]), int(target_ip[2]), int(target_ip[3]))
			}
		)

	# Get current domain IP.
	def get_ip(self):
		return requests.get(
			'https://api.teainside.org/internal.php',
			auth=TokenAuth(token),
			params={
				'action': 'nginx_prox',
				'domain': self.domain,
				'sb_act': 'get'
			}
		)



token = "EcppB0QIiAaJ/fy6nQxhIvFPbAquTZ162mCbCLL3ooJ0THiQ+/T1xPYLsTLnRBVJfwAvDCVh9C2+"
domain = "starlight.teainside.org"
st = SimpleTeaProxDomainResolver(token, domain)

# Set domain IP to 123.123.123.123
# 
print(st.set_ip("139.180.212.115").text)

# # Get current domain IP
# print(st.get_ip().text)