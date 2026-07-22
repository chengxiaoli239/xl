import configparser
import traceback


def p(a='', b='', c='', d=''):
    print(a, b, c, d)
    return
    try:
        conf = configparser.ConfigParser()
        conf.read("./systemConfigs.conf", encoding='utf8')
        debug = conf.get('system_configs', 'debug')
        if debug == '1':
            print(a, b, c, d)
    except Exception as e:
        print('ppp_err:', e.args, a)
        traceback.print_exc()
        #print(a, b, c, d)
        pass

