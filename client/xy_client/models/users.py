import time

from xy_client.models.common import dbConnect


def createUsersTbl():
    # 数据库
    conn = dbConnect()
    # 创建一个游标
    cursor = conn.cursor()
    # 创建一个表
    flag = cursor.execute('''CREATE TABLE users
                      (id INTEGER PRIMARY KEY,
                       username TEXT,
                       mobile_phone TEXT)''')
    return flag


def insertUsers(fields, datas):
    lenght = len(fields)
    value_str = ('?, '*lenght).strip(', ')
    print('value_str', value_str)
    # 数据库
    conn = dbConnect()
    # 创建一个游标
    cursor = conn.cursor()
    #sql = "INSERT INTO users (name, mobile_phone) VALUES (?, ?)", ('Jane Doe', '15008080609')
    sql = "INSERT INTO users ("+', '.join(fields)+") VALUES ("+value_str+")"
    print('insert_sql', sql)
    #value_data = '(\'' + "', '".join(datas) + '\')'
    #print('value_data', value_data)
    cursor.execute(sql, datas)

    # 提交修改
    flag = conn.commit()

    # 关闭连接
    conn.close()

    return flag


def selectUsers(where):
    # 数据库
    conn = dbConnect()
    # 创建一个游标
    cursor = conn.cursor()
    # 查找数据
    select_sql = "SELECT * FROM users WHERE "+where['key']+"='"+str(where['value'])+"'"
    print('select_sql：', select_sql)
    cursor.execute(select_sql)
    rows = cursor.fetchone()

    # 输出结果
    if rows:
        print(rows)
    else:
        print('No results found')

    # 关闭连接
    cursor.close()
    conn.close()

    return rows


def updateUserAgentInfo(updateData=None, access_token=''):
    # 数据库
    if updateData is None:
        updateData = {}
    
    # 验证必要数据
    if not access_token:
        print('⚠️ updateUserAgentInfo: access_token为空，无法保存')
        return None
    
    if not updateData.get('agent_domain') or not updateData.get('agent_account') or not updateData.get('agent_password'):
        print(f'⚠️ updateUserAgentInfo: 数据不完整，无法保存 - domain: {bool(updateData.get("agent_domain"))}, account: {bool(updateData.get("agent_account"))}, password: {bool(updateData.get("agent_password"))}')
        return None
    
    conn = dbConnect()
    # 创建一个游标
    cursor = conn.cursor()
    current_timestamp = round(time.time())

    try:
        # 查询数据
        select_sql = "SELECT * FROM users WHERE access_token = ?"
        cursor.execute(select_sql, (access_token,))
        row = cursor.fetchone()

        if row is None:
            # 如果查询结果为空，插入一条记录
            insert_sql = "INSERT INTO users (access_token, agent_domain, agent_account, agent_password, updated_at, created_at) VALUES (?, ?, ?, ?, ?, ?)"
            cursor.execute(insert_sql, (access_token, updateData['agent_domain'], updateData['agent_account'], updateData['agent_password'], current_timestamp, current_timestamp))
            print(f'✅ updateUserAgentInfo: 插入新记录成功 - access_token: {access_token[:20]}...')
        else:
            # 如果查询结果存在，更新记录
            update_sql = "UPDATE users SET agent_domain = ?, agent_account = ?, agent_password = ?, updated_at = ? WHERE access_token = ?"
            cursor.execute(update_sql, (updateData['agent_domain'], updateData['agent_account'], updateData['agent_password'], current_timestamp, access_token))
            print(f'✅ updateUserAgentInfo: 更新记录成功 - access_token: {access_token[:20]}...')
        
        # 提交事务
        conn.commit()
        
        # 验证保存结果
        cursor.execute(select_sql, (access_token,))
        row = cursor.fetchone()
        if row:
            print(f'✅ updateUserAgentInfo: 保存验证成功')
        else:
            print(f'⚠️ updateUserAgentInfo: 保存后验证失败，记录不存在')
            
    except Exception as e:
        print(f'❌ updateUserAgentInfo: 保存失败 - {e}')
        import traceback
        traceback.print_exc()
        conn.rollback()
        row = None
    finally:
        # 关闭游标和数据库连接
        cursor.close()
        conn.close()

    return row


def deleteUserById(recordId):
    '''
    删除数据库记录
    '''
    # 数据库
    conn = dbConnect()
    # 创建一个游标
    cursor = conn.cursor()
    # 查找数据
    delete_sql = "DELETE FROM users WHERE id='"+recordId+"'"
    print('delete_sql：', delete_sql)
    cursor.execute(delete_sql)

    conn.commit()
    # 关闭连接
    conn.close()

    return True


def updateUserStatusById(recordId, status=1, desc='已启动...'):
    '''
    删除数据库记录
    '''
    # 数据库
    conn = dbConnect()
    # 创建一个游标
    cursor = conn.cursor()
    # 查找数据
    update_sql = "UPDATE users set status="+str(status)+", desc='"+desc+"' WHERE id='"+recordId+"'"
    print('update_sql：', update_sql)
    cursor.execute(update_sql)

    conn.commit()
    # 关闭连接
    conn.close()

    return True
