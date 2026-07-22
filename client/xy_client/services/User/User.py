import time

import json
import requests

from xy_client.services.tools.Configs import Configs


class User:

    def updateTzSystemUsersByField(id, field):
        robot_domain = Configs().get_config('robot_domain').rstrip('/')
        url = robot_domain + '/api/index/switch-status'
        post_data = {'id': id, 'field': field}

        headers = {'content-type': 'application/json'}

        rst = requests.post(url, data=json.dumps(post_data), headers=headers, timeout=12)
        data = rst.json()
        print(data)
        now_time = time.strftime("%Y-%m-%d %H:%M:%S", time.localtime())
        print(now_time)

        return data
