#coding=utf-8

from datetime import datetime
import json
from kaijiang import *



def my_kj():
	f = open("config.json")
	dbconfig = json.load(f)
	jiang = KaiJiang(dbconfig)
	jiang.TimeFactor()
	print 'time: %s' % datetime.now()

def my_kj1():
	print 'time: %s' % ((336306645+99096)%60+10000001)
	debugstr = 'pid | time: %s,%s' % (11,datetime.now())
	print debugstr
	file_object = open('log.txt','a+')
	file_object.write( debugstr )
	file_object.close()


my_kj1()