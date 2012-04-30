#! /usr/bin/python
#import re
import os
import sys
from pydas import core
#import time
#import pydas.communicator as apiMidas
#import pydas.exceptions as pydasException
#import uuid
#import json
#import shutil
#from zipfile import ZipFile, ZIP_DEFLATED
#from subprocess import Popen, PIPE, STDOUT
#from contextlib import closing

# Load configuration file
def loadConfig(filename):
   try:
     configfile = open(filename, "r")
     ret = dict()
     for x in configfile:
       x = x.strip()
       if not x: continue
       cols = x.split()
       #print cols
       ret[cols[0]] = cols[1]
     return ret
   except Exception, e: raise




def parseVolumeMeasurement(filepath):
  if not os.path.exists(filepath):
    return None
  lines = open(filepath, 'r')
  volume = "n/a"
  #  pattern = re.compile("Volume of segmentation (mm^3) = 465.686
  for line in lines:
    line = line.strip()
    if line.find("Volume of segmentation (mm^3) = ") > -1:
      cols = line.split()
      volume = cols[-1]
  lines.close()
  return volume


def addRunItemScalarvalue(communicator, token, qibenchrunitemid, name, value):
    """
    Adds a scalar value to the runitem
    """
    parameters = dict()
    parameters['token'] = token
    parameters['qibenchrunitemid'] = qibenchrunitemid
    parameters['name'] = name
    parameters['value'] = value
    #print parameters
    response = communicator.makeRequest('midas.qibench.runitemscalarvalue.add', parameters)
    return response



def setRunItemOutputItemId(communicator, token, qibenchrunitemid, outputItemId):
    """
    Sets the outputItemId on the runitem
    """
    parameters = dict()
    parameters['token'] = token
    parameters['qibenchrunitemid'] = qibenchrunitemid
    parameters['outputitemid'] = outputItemId
    print parameters
    response = communicator.makeRequest('midas.qibench.runitem.outputitemid.set', parameters)
    return response

def addCondorJob(communicator, token, taskid, jobdefinitionfilename, outputfilename, errorfilename, logfilename, postfilename):
    """
    Adds the condor_job row
    """
    parameters = dict()
    parameters['token'] = token
    parameters['batchmaketaskid'] = taskid
    parameters['jobdefinitionfilename'] = jobdefinitionfilename
    parameters['outputfilename'] = outputfilename
    parameters['errorfilename'] = errorfilename
    parameters['logfilename'] = logfilename
    parameters['postfilename'] = postfilename
    parameters['XDEBUG_SESSION_START'] = 'netbeans-xdebug'
    print parameters
    response = communicator.makeRequest('midas.batchmake.add.condor.job', parameters)
    return response


def setRunitemCondorjob(communicator, token, qibenchrunitemid, condorjobid):
    """
    Sets the condor job id on the runitem
    """
    parameters = dict()
    parameters['token'] = token
    parameters['qibenchrunitemid'] = qibenchrunitemid
    parameters['condorjobid'] = condorjobid
    print parameters
    response = communicator.makeRequest('midas.qibench.runitem.condorjob.set', parameters)
    return response



if __name__ == "__main__":
  print sys.argv
  (scriptName, outputFolderId, outputFile, job, jobname, returncode) = sys.argv

#  print "outputDir: ",outputDir
  parts = outputFile.split('/')
#  print parts[-1]
#  print parts[:-1]
  outputDir = '/'.join(parts[:-1])
  outputFile = parts[-1]

  print "outputFile: ",outputFile
  print "outputDir: ",outputDir

  jobidNum = jobname[3:]
  cfgParams = loadConfig('config.cfg')

  postfilename = 'postscript'+jobidNum+'.log'
  log = open(os.path.join(outputDir, postfilename),'w')
  log.write('Condor Post Script log\n\nsys.argv:\n\n')
  log.write('\t'.join(sys.argv))

  log.write('\n\nConfig Params:\n\n')
  log.write('\n'.join(['\t'.join((k,v)) for (k,v) in cfgParams.iteritems()])) 

  interfaceMidas = core.Communicator (cfgParams['url'])
  token = interfaceMidas.login_with_api_key(cfgParams['email'], cfgParams['apikey'], application='Default')
  log.write("\n\nLogged into midas, got token: "+token+"\n\n")
  exeOutput = 'bmGrid.' + jobidNum + '.out.txt' 
  exeOutputPath = os.path.join(outputDir, exeOutput)
  

  # create the item
  item = interfaceMidas.create_item(token, outputFile, outputFolderId)
  itemId = item['item_id']
  log.write("\n\nCalled createItem, got itemId:"+str(itemId)+"\n\n")


  filePath = outputDir + '/' + outputFile
  uploadToken = interfaceMidas.generate_upload_token(token, itemId, outputFile)
  log.write("\n\nGot uploadToken:"+str(uploadToken)+" for filename "+outputFile+"\n\n")

  uploadResponse = interfaceMidas.perform_upload(uploadToken, outputFile, itemid=itemId, filepath=filePath)
  log.write("\n\nGot uploadResponse:"+str(uploadResponse)+" for filename "+outputFile+"\n\n")

  exit()
