import os
import sqlite3
import sys


def dbConnect():
    # 获取可执行文件所在的目录
    if getattr(sys, 'frozen', False):
        # 如果是打包后的可执行文件
        application_path = os.path.dirname(sys.executable)
    else:
        # 如果是开发环境中的脚本
        # 从当前工作目录查找xy_client目录
        current_dir = os.getcwd()
        if 'xy_client' in current_dir:
            # 如果当前在xy_client目录内
            if current_dir.endswith('xy_client'):
                application_path = current_dir
            else:
                # 如果在xy_client的子目录中
                xy_client_index = current_dir.find('xy_client')
                application_path = current_dir[:xy_client_index + 9]
        else:
            # 如果在项目根目录，需要进入xy_client目录
            application_path = os.path.join(current_dir, 'xy_client')
    db_path = os.path.join(application_path, 'data', 'lucky.db')
    os.makedirs(os.path.dirname(db_path), exist_ok=True)
    print('db_path:', db_path)
    # 连接数据库（如果不存在，则创建一个新的数据库）
    conn = sqlite3.connect(db_path)

    return conn
