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
  (scriptName, outputDir, taskId, outputImage, outputFolderId, dagname, job, jobname, returncode) = sys.argv
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
  #log.write("\n\nParsing output file: "+exeOutputPath+"\n\n")
  #volume = parseVolumeMeasurement(exeOutputPath)
  #log.write("\n\nvolume from output file:"+volume+"\n\n")
  
  jobdefinitionfilename = dagname +'.'+jobidNum+'.dagjob' 
  exeError = 'bmGrid.' + jobidNum + '.error.txt' 
  exeLog = 'bmGrid.' + jobidNum + '.log.txt' 

  # create the item
  item = interfaceMidas.create_item(token, outputImage, outputFolderId)
  itemId = item['item_id']
  log.write("\n\nCalled createItem, got itemId:"+str(itemId)+"\n\n")


  filePath = outputDir + '/' + outputImage
  uploadToken = interfaceMidas.generate_upload_token(token, itemId, outputImage)
  log.write("\n\nGot uploadToken:"+str(uploadToken)+" for filename "+outputImage+"\n\n")
    #print uploadToken


  uploadResponse = interfaceMidas.perform_upload(uploadToken, outputImage, itemid=itemId, filepath=filePath)
#length, filePath, None, None, itemId, 'head')
  log.write("\n\nGot uploadResponse:"+str(uploadResponse)+" for filename "+outputImage+"\n\n")
#c.perform_upload('1/148/input.nrrdrj1R20','input.nrrd',filepath='/home/mgrauer/dev/bin/rodentimaging/unu_dev/139_20110207_crews_overnight_9_bias_corrected_corrected.nrrd',itemid='148')


  exit()













# need to fix the condor stuff later on
  response = addCondorJob(interfaceMidas, token, taskId, jobdefinitionfilename, exeOutput, exeError, exeLog, postfilename)
  log.write("\n\nCalled addCondorJob() with response:"+str(response)+"\n\n")
  condorjobid = response['condor_job_id']

  response = setRunitemCondorjob(interfaceMidas, token, runItemId, condorjobid)
  log.write("\n\nCalled setRunitemCondorjob() with response:"+str(response)+"\n\n")

  response = addRunItemScalarvalue(interfaceMidas, token, runItemId, 'CaseReading', volume)
  log.write("\n\nCalled addRunItemScalarvalue("+runItemId+", "+"CaseReading"+", "+volume+") with response:"+str(response)+"\n\n")

  


  #itemId = 190 # hardcode for now
  # set the outputitemid in the runitem
  response = setRunItemOutputItemId(interfaceMidas, token, runItemId, itemId)
  log.write("\n\nCalled setRunItemOutputItemId with response:"+str(response)+"\n\n")


 
  #print cfgParams
  

  #exit()
  #bmGrid.1.out.txt
  # HACK need some error handling if no file
  # also look at returncode value
 

  #exit()
  #exit()
  #qibench_run_item_id 


 
  # also get the revision and set to head

  # upload the bitstreams to the item
  for filename in [outputAim, outputImage, outputMesh]:
    filePath = outputDir + '/' + filename
    uploadToken = interfaceMidas.generateUploadToken(token, itemId, filename)
    log.write("\n\nGot uploadToken:"+str(uploadToken)+" for filename "+filename+"\n\n")
    length = os.path.getsize(filePath)
    #print uploadToken
    uploadResponse = interfaceMidas.performUpload(uploadToken['token'], filename, length, filePath, None, None, itemId, 'head')
    log.write("\n\nGot uploadResponse:"+str(uploadResponse)+" for filename "+filename+"\n\n")
  
  log.close()
  exit()#print cfgParamsOut
