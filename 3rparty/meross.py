#!/usr/bin/python3

debug = True

import time
import sys
import os
import pprint
import json
import pickle
import argparse
from datetime import datetime, timedelta

if sys.version_info[0] < 3:
    raise Exception("Must be using Python 3")

# Get Python version
pver=str(sys.version_info.major) + '.' + str(sys.version_info.minor)

# Add Meross-iot lib to Pythonpath
current_dir=os.path.normpath(os.path.dirname(os.path.abspath(os.path.realpath(sys.argv[0]))))
#sys.path.append(os.path.abspath(os.path.join(current_dir, 'meross_iot', 'lib', 'python' + pver, 'site-packages')))

# Var dir
var_dir = current_dir

# Meross lib
from meross_iot.api import MerossHttpClient

# data files
conffile = os.path.join(var_dir, 'config.ini')
pklfile  = os.path.join(var_dir, 'result.pkl')
jsonfile = os.path.join(var_dir, 'result.json')



# ---------------------------------------------------------------------
def ReadConfig(conffile=conffile):
    """ Read config (secrets) """
    import configparser
    config = configparser.ConfigParser()
    try:
        config.read(conffile)
        email = config.get('secret', 'email')
        password = config.get('secret', 'password')
    except:
        print("""
>>>> Error : wrong file 'config.ini' ! Please create this file with this contents :

[secret]
email = your-meros-email-account
password = your-meross-password

""")
        sys.exit(1)
    return email, password


# ---------------------------------------------------------------------
def Exit(txt=""):
    print(txt)
    sys.exit(1)



# ---------------------------------------------------------------------
def ConnectAndRefreshAll(email, password):
    """ Connect to Meross Cloud and refresh all devices and informations """

    try:
        httpHandler = MerossHttpClient(email, password)
    except:
        Exit("<F> Error : can't connect to Meross Cloud ! Please verify Internet connection, email and password !")

    # Retrieves the list of supported devices
    devices = httpHandler.list_supported_devices()

    #print(devices)
    #print(50*'=')

    # Build dict of devices informations
    d_devices = {}

    for num in range(len(devices)):
        if debug: print(50*'=' + '\nnum=', num)
        device = devices[num]
        data = device.get_sys_data()
        #pprint.pprint(data)
        #pprint.pprint(device)
        d_devices[num] = dict( {
            'name':   device._name,
            'ip':     data['all']['system']['firmware']['innerIp'],
            'mac':    data['all']['system']['hardware']['macAddress'],
            'online': data['all']['system']['online']['status'],
            'uuid':   data['all']['system']['hardware']['uuid'],
            'type':   data['all']['system']['hardware']['type'],
            } )
        try:
            electricity = device.get_electricity()
            d_devices[num]['power'] = electricity['electricity']['power']
        except:
            d_devices[num]['power'] = '-1'

        try:
            d_devices[num]['consumption'] = device.get_power_consumptionX()['consumptionx']
        except:
            d_devices[num]['consumption'] = []

        #now = datetime.datetime.now()
        #yesterday = now.strftime("%Y-%m-%d")
        today = datetime.today()
        yesterday = (today - timedelta(1) ).strftime("%Y-%m-%d")
        d_devices[num]['consumption_yesterday'] = -1
        for c in d_devices[num]['consumption']:
            if c['date'] == yesterday:
                try:
                    d_devices[num]['consumption_yesterday'] = c['value']
                except:
                    d_devices[num]['consumption_yesterday'] = -1
                break

    if debug: pprint.pprint(d_devices)

    # Save dictionnary into Pickle file
    f = open(pklfile,"wb")
    pickle.dump(d_devices,f)
    f.close()

    # Save dictionnary into JSON file
    l_devices = list(d_devices.values())
    #print(l_devices)
    with open(jsonfile, 'w') as fp:
        json.dump(l_devices, fp)
    return d_devices

# ---------------------------------------------------------------------
def GetByName(d_devices, name):
    """ Find a Meross Smartplug with name """
    for k in d_devices.keys():
        if (d_devices[k]['name'] == name ):
            return d_devices[k]
    return {}

# ---------------------------------------------------------------------
def GetByUuid(d_devices, uuid):
    """ Find a Meross Smartplug with uuid """
    for k in d_devices.keys():
        if (d_devices[k]['uuid'] == uuid ):
            return d_devices[k]
    return {}

# ---------------------------------------------------------------------
def GetByMAC(d_devices, mac):
    """ Find a Meross Smartplug with MAC """
    for k in d_devices.keys():
        if (d_devices[k]['mac'] == mac ):
            return d_devices[k]
    return {}

# ---------------------------------------------------------------------
if __name__=='__main__':

    # Arguments
    parser = argparse.ArgumentParser(description='Meross Python lib for Nextdom')
    parser.add_argument('--refresh', action="store_true", default=False)
    parser.add_argument('--uuid', action="store", dest="uuid")
    parser.add_argument('--name', action="store", dest="name")
    parser.add_argument('--mac', action="store", dest="mac")
    parser.add_argument('--channel', action="store", dest="channel")
    parser.add_argument('--set_on', action="store_true", default=False)
    parser.add_argument('--set_off', action="store_true", default=False)
    parser.add_argument('--show_power', action="store_true", default=False)
    parser.add_argument('--show_yesterday', action="store_true", default=False)
    parser.add_argument('--show', action="store_true", default=False)
    parser.add_argument('--config', action="store", dest="config")

    args = parser.parse_args()
    #print(args)

    # Read config file
    if args.config:
        if not os.isfile(args.config):
            Exit("<F> Error : can't read '%s' config file!")
        else:
            conf=args.config
    else:
        conf=conffile
    email, password = ReadConfig(conffile=conf)

    # Connect to Meross Cloud and refresh all devices and informations
    refresh = False
    if not args.refresh:
        try:
            # Read local saved data
            f = open(pklfile,"rb")
            d_devices = pickle.load(f)
            f.close()
            #pprint.pprint(d_devices)
        except:
            refresh = True
    if args.refresh or refresh:
        d_devices = ConnectAndRefreshAll(email, password)

    # Find the Smartplug
    SP = None
    if args.name:
        if debug: print("<I> Getting informations for Smartplug named '%s' ..." % args.name)
        SP = GetByName(d_devices=d_devices, name=args.name)
    elif args.uuid:
        if debug: print("<I> Getting informations for Smartplug with uuid '%s' ..." % args.uuid)
        SP = GetByUuid(d_devices=d_devices, uuid=args.uuid)
    elif args.mac:
        if debug: print("<I> Getting informations for Smartplug with MAC '%s' ..." % args.mac)
        SP = GetByMAC(d_devices=d_devices, mac=args.mac)

    #if not SP:
    #    Exit("<F> Error : can't find the Smartplug ... Please specify --uuid, --name or --mac parameter !")


    # Affichage sur la stdout
    if SP:
        if debug: pprint.pprint(SP)
        if args.show:
            pprint.pprint(SP)

        # Return only Power value
        if args.show_power:
            print (str(int(float(SP['power']/1000.))))

        # Return only yesterday Consumption value
        if args.show_yesterday:
            print (str(int(float(SP['consumption_yesterday']/100.))/10.))

    else:
        if args.show:
            pprint.pprint(d_devices)




    """
    # Returns most of the info about the power plug
    data = devices[0].get_sys_data()

    # Turns the power-plug on
    devices[0].turn_off()

    # Turns the power-plug off
    devices[0].turn_on()

    # Reads the historical device consumption
    consumption = devices[0].get_power_consumptionX()

    # Returns the list of WIFI Network available for the plug
    # (Note. this takes some time to complete)
    wifi_list = devices[0].get_wifi_list()

    # Info about the device
    trace = devices[0].get_trace()
    debug = devices[0].get_debug()

    # Returns the capabilities of this device
    abilities = devices[0].get_abilities()

    # I still have to figure this out :S
    report = devices[0].get_report()

    # Returns the current power consumption and voltage from the plug
    # (Note: this is not really realtime, but close enough)
    electricity = devices[0].get_electricity()

    current_status = devices[0].get_electricity()
    print(current_status)
    """

