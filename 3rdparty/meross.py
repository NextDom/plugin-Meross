#!/usr/bin/python3

import os
import time
from random import randint

from meross_iot.cloud.devices.door_openers import GenericGarageDoorOpener
from meross_iot.cloud.devices.hubs import GenericHub
from meross_iot.cloud.devices.light_bulbs import GenericBulb
from meross_iot.cloud.devices.power_plugs import GenericPlug
from meross_iot.cloud.devices.subdevices.thermostats import ValveSubDevice, ThermostatV3Mode
from meross_iot.manager import MerossManager
from meross_iot.meross_event import MerossEventType
from datetime import datetime, timedelta
import argparse
import pickle
import json
import pprint
import os
import sys
debug = False


if sys.version_info[0] < 3:
    raise Exception("Must be using Python 3")

# Get Python version
pver = str(sys.version_info.major) + '.' + str(sys.version_info.minor)

# Add Meross-iot lib to Pythonpath
current_dir = os.path.normpath(os.path.dirname(os.path.abspath(os.path.realpath(sys.argv[0]))))
#sys.path.append(os.path.abspath(os.path.join(current_dir, 'meross_iot', 'lib', 'python' + pver, 'site-packages')))

# Var dir
var_dir = current_dir

# Meross lib

# data files
conffile = os.path.join(var_dir, 'config.ini')
# pklfile  = os.path.join(var_dir, 'result.pkl')
# jsonfile = os.path.join(var_dir, 'result.json')

# ---------------------------------------------------------------------


class WriteLog:
    def __init__(self):
        self.debug = debug

    def p(self, txt):
        if self.debug:
            print(txt)
        return

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
email = your-meross-email-account
password = your-meross-password

""")
        sys.exit(1)
    return email, password


# ---------------------------------------------------------------------
def Exit(txt=""):
    print(txt)
    sys.exit(1)


# ---------------------------------------------------------------------
def RefreshOneDevice(device):
    """ Connect to Meross Cloud and refresh only the device 'device' """

    data = device.get_sys_data()
    if debug:
        pprint.pprint(data)
    # pprint.pprint(device)

    d = dict({
        'name':     device._name,
        'uuid':     device._uuid,
        'ip':       '',
        'mac':      '',
        'online':   '',
        'type':     '',
        'firmversion': '',
        'hardversion': '',
    })

    try:
        d['ip'] = data['all']['system']['firmware']['innerIp']
    except:
        pass

    try:
        d['mac'] = data['all']['system']['hardware']['macAddress']
    except:
        pass

    try:
        d['online'] = data['all']['system']['online']['status']
    except:
        pass

    try:
        d['type'] = data['all']['system']['hardware']['type']
    except:
        pass

    try:
        d['firmversion'] = data['all']['system']['firmware']['version']
    except:
        pass

    try:
        d['hardversion'] = data['all']['system']['hardware']['version']
    except:
        pass

    # on/off status
    onoff = []
    try:
        #print (data)
        #val = str(data['all']['control']['toggle']['onoff'])
        #print (val)
        # val = ''.join(val.split())   # suppress all blank, new lines, ..
        #print (val)
        onoff = [data['all']['control']['toggle']['onoff']]
    except:
        try:
            ll = data['all']['digest']['togglex']
            onoff = [x['onoff'] for x in ll]
        except:
            pass
    d['onoff'] = onoff

    # Current power
    try:
        electricity = device.get_electricity()
        d['power'] = float(electricity['electricity']['power'] / 1000.)
    except:
        d['power'] = '-1'

    # Historical consumption
    try:
        l_consumption = device.get_power_consumptionX()['consumptionx']
    except:
        l_consumption = []

    d['consumption'] = []   # on decide de ne pas la stocker

    # Yesterday consumption
    today = datetime.today()
    yesterday = (today - timedelta(1)).strftime("%Y-%m-%d")
    d['consumption_yesterday'] = 0

    for c in l_consumption:
        if c['date'] == yesterday:
            try:
                d['consumption_yesterday'] = float(c['value'] / 1000.) 
            except:
                d['consumption_yesterday'] = 0
            break

    return d

# ---------------------------------------------------------------------
def event_handler(eventobj):
    if eventobj.event_type == MerossEventType.DEVICE_ONLINE_STATUS:
        print("Device online status changed: %s went %s" % (eventobj.device.name, eventobj.status))
        pass

    elif eventobj.event_type == MerossEventType.DEVICE_SWITCH_STATUS:
        print("Switch state changed: Device %s (channel %d) went %s" % (eventobj.device.name, eventobj.channel_id,
                                                                        eventobj.switch_state))
    elif eventobj.event_type == MerossEventType.CLIENT_CONNECTION:
        print("MQTT connection state changed: client went %s" % eventobj.status)

        # TODO: Give example of reconnection?

    elif eventobj.event_type == MerossEventType.GARAGE_DOOR_STATUS:
        print("Garage door is now %s" % eventobj.door_state)

    elif eventobj.event_type == MerossEventType.THERMOSTAT_MODE_CHANGE:
        print("Thermostat %s has changed mode to %s" % (eventobj.device.name, eventobj.mode))

    elif eventobj.event_type == MerossEventType.THERMOSTAT_TEMPERATURE_CHANGE:
        print("Thermostat %s has revealed a temperature change: %s" % (eventobj.device.name, eventobj.temperature))

    else:
        print("Unknown event!")

# ---------------------------------------------------------------------
def ConnectAndRefreshAll(email, password):
    """ Connect to Meross Cloud and refresh all devices and informations """

    try:       
        # Initiates the Meross Cloud Manager. This is in charge of handling the communication with the remote endpoint
        manager = MerossManager(meross_email=email, meross_password=password)
        
        # Register event handlers for the manager...
        manager.register_event_handler(event_handler)

        # Starts the manager
        manager.start()
    except:
        Exit("<F> Error : can't connect to Meross Cloud ! Please verify Internet connection, email and password !")

    # Retrieves the list of supported devices
    # devices = httpHandler.list_supported_devices()
    devices = manager.get_supported_devices()

    # Build dict of devices informations
    d_devices = {}

    for num in range(len(devices)):
        if debug:
            print(50*'=' + '\nnum=', num)
        device = devices[num]

        d = RefreshOneDevice(device=device)

        uuid = device._uuid
        d_devices[uuid] = d

    if debug:
        pprint.pprint(d_devices)

    # Save dictionnary into Pickle file
    # f = open(pklfile,"wb")
    # pickle.dump(d_devices,f)
    # f.close()

    # Save dictionnary into JSON file
    # l_devices = list(d_devices.values())
    # print(l_devices)
    # with open(jsonfile, 'w') as fp:
    #   json.dump(d_devices, fp)

    manager.stop()

    return d_devices

# ---------------------------------------------------------------------


def ConnectAndSetOnOff(devices, email, password, name=None, uuid=None, mac=None, action='on', channel='0'):
    """ Connect to Meross Cloud and set on or off a smartplug """

    if mac and not name and not uuid:
        Exit("<F> Error : not implemented !")
    if not name and not uuid and not mac:
        Exit("<F> Error : need at least 'name', 'uuid' or 'mac' parameter to set on or off a smartplug !")

    try:
        # Initiates the Meross Cloud Manager. This is in charge of handling the communication with the remote endpoint
        manager = MerossManager(meross_email=email, meross_password=password)
        
        # Register event handlers for the manager...
        manager.register_event_handler(event_handler)

        # Starts the manager
        manager.start()
    except:
        Exit("<F> Error : can't connect to Meross Cloud ! Please verify Internet connection, email and password !")

    # Retrieves the list of supported devices
    ldevices = manager.get_supported_devices()

    device = None
    for d in ldevices:
        if (d._uuid == uuid) or (d._name == name):
            device = d
            break

    uuid = d._uuid
    #pprint.pprint( devices[uuid] )
    if len(devices[uuid]['onoff']) == 1:
        try:
            if action == 'on':
                device.turn_on()
            else:
                device.turn_off()
        except:
            pass
    else:
        try:
            if action == 'on':
                if channel == '0':
                    device.turn_on()
                else:
                    device.turn_on_channel(channel)
            else:
                if channel == '0':
                    device.turn_off()
                else:
                    device.turn_off_channel(channel)
        except:
            pass

    devices[d._uuid] = RefreshOneDevice(device)

    manager.stop()

    return devices

# ---------------------------------------------------------------------


def GetByName(d_devices, name):
    """ Find a Meross Smartplug from name """
    for k in d_devices.keys():
        if (d_devices[k]['name'] == name):
            return d_devices[k]
    return {}

# ---------------------------------------------------------------------


def GetByUuid(d_devices, uuid):
    """ Find a Meross Smartplug from uuid """
    for k in d_devices.keys():
        if (d_devices[k]['uuid'] == uuid):
            return d_devices[k]
    return {}

# ---------------------------------------------------------------------


def GetByMAC(d_devices, mac):
    """ Find a Meross Smartplug from MAC """
    for k in d_devices.keys():
        if (d_devices[k]['mac'] == mac):
            return d_devices[k]
    return {}


# ---------------------------------------------------------------------
if __name__ == '__main__':

    # Arguments
    parser = argparse.ArgumentParser(description='Meross Python lib for Nextdom')
    parser.add_argument('--refresh', action="store_true", default=False)
    parser.add_argument('--uuid', action="store", dest="uuid")
    parser.add_argument('--name', action="store", dest="name")
    parser.add_argument('--mac', action="store", dest="mac")
    parser.add_argument('--channel', action="store", dest="channel", default="0")
    parser.add_argument('--set_on', action="store_true", default=False)
    parser.add_argument('--set_off', action="store_true", default=False)
    parser.add_argument('--show_power', action="store_true", default=False)
    parser.add_argument('--show_yesterday', action="store_true", default=False)
    parser.add_argument('--show', action="store_true", default=False)
    parser.add_argument('--email', action="store", dest="email")
    parser.add_argument('--password', action="store", dest="password")
    parser.add_argument('--config', action="store", dest="config")
    parser.add_argument('--debug', action="store_true", default=False)

    args = parser.parse_args()
    # print(args)

    # WriteLog
    l = WriteLog()
    l.debug = args.debug

    # Refresh all devices and informations from local file
    refresh = True
    # if not args.refresh:
    #    try:
    #        # Read local saved data
    #        f = open(pklfile, "rb")
    #        d_devices = pickle.load(f)
    #        f.close()
    #        # pprint.pprint(d_devices)
    #    except:
    #        refresh = True

    # Get email / password (only if necessary : refresh or action)
    if args.refresh or refresh or args.set_on or args.set_off:
        email = None
        password = None
        # Get from commandline argument
        if args.email and args.password:
            email = args.email
            password = args.password
        # Get from config file (commandline argument)
        elif args.config:
            if not os.isfile(args.config):
                Exit("<F> Error : can't read '%s' config file!" % args.config)
            else:
                try:
                    email, password = ReadConfig(conffile=args.config)
                except:
                    Exit("<F> Error : can't read '%s' config file!" % args.config)
        # Get from local config file
        elif os.path.isfile(conffile):
            try:
                email, password = ReadConfig(conffile=conffile)
            except:
                Exit("<F> Error : can't read '%s' config file!" % args.config)

        # If not defined --> error
        if not email or not password:
            Exit("<F> Error : Can't get email and password !")

    # Connect to Meross Cloud and Refresh
    if args.refresh or refresh:
        d_devices = ConnectAndRefreshAll(email, password)

    # Set on / off
    if args.set_on:
        d_devices = ConnectAndSetOnOff(devices=d_devices, email=email, password=password,
                                       name=args.name, uuid=args.uuid, mac=args.mac, action='on', channel=args.channel)
    if args.set_off:
        d_devices = ConnectAndSetOnOff(devices=d_devices, email=email, password=password,
                                       name=args.name, uuid=args.uuid, mac=args.mac, action='off', channel=args.channel)

    # Find the Smartplug
    SP = None
    if args.name:
        if debug:
            print("<I> Getting informations for Smartplug named '%s' ..." % args.name)
        SP = GetByName(d_devices=d_devices, name=args.name)
    elif args.uuid:
        if debug:
            print("<I> Getting informations for Smartplug with uuid '%s' ..." % args.uuid)
        SP = GetByUuid(d_devices=d_devices, uuid=args.uuid)
    elif args.mac:
        if debug:
            print("<I> Getting informations for Smartplug with MAC '%s' ..." % args.mac)
        SP = GetByMAC(d_devices=d_devices, mac=args.mac)
    else:
        SP = d_devices

    # Return only Power value
    if args.show_power:
        print(str(int(float(SP['power']/1000.))))

    # Return only yesterday Consumption value
    elif args.show_yesterday:
        print(str(int(float(SP['consumption_yesterday']/1000.))))

    # Return the JSON output
    elif args.show:
        # pprint.pprint(SP)
        #jsonarray = json.dumps(SP)
        if args.name or args.uuid or args.mac:
            d = dict({SP['uuid']: SP})
        else:
            d = d_devices
        jsonarray = json.dumps(d, indent=4, sort_keys=True)
        print(jsonarray)

    # Save dictionnary into JSON file
    # l_devices = list(d_devices.values())
    # print(l_devices)
    # with open(jsonfile, 'w') as fp:
    #    json.dump(d_devices, fp)
